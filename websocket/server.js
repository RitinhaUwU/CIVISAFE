import { Server } from 'socket.io'
import Redis from 'ioredis'

export const server = {
  io: null,
}

export const serverStart = (port, rHost, rPort, backendURL) => {

  server.io = new Server(port, {
    cors: {
      origin: '*',
    },
  })

  const redis = new Redis({
    host: rHost,
    port: rPort,
  })

  const userSockets = new Map()

  const addUserSocket = (userId, socket) => {
    const sockets = userSockets.get(userId) || new Set()
    sockets.add(socket)
    userSockets.set(userId, sockets)
  }

  const removeUserSocket = (userId, socket) => {
    const sockets = userSockets.get(userId)
    if (!sockets) return
    sockets.delete(socket)
    if (sockets.size === 0) {
      userSockets.delete(userId)
    }
  }

  redis.subscribe('user.notifications');
  redis.subscribe('ui.updates');

  redis.on('message', (channel, message) => {
    console.debug(channel, message)

    try {
      const payload = JSON.parse(message)

      if(channel === "user.notifications")
      {
        if (payload.event === 'notification' && payload.user_id) {
          const sockets = userSockets.get(payload.user_id)
          if (sockets) {
            sockets.forEach((socket) => socket.emit('notification', payload.data))
          }
        }
      }

      if(channel === "ui.updates")
      {
        server.io.emit('ui_update', payload)
      }

    } catch (e) {
      console.error('[REDIS] ' + e.message)
    }
  })

  server.io.use(async (socket, next) => {
    const token = socket.handshake.auth?.token
    if (!token) {
      return next(new Error('Authentication required'))
    }

    try {
      const response = await fetch(`${backendURL}/user`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })

      if (!response.ok) {
        throw new Error('Unauthorized')
      }

      const user = await response.json()
      socket.data.user = user
      addUserSocket(user.id, socket)
      next()
    } catch (error) {
      console.error('[SOCKET AUTH]', error.message)
      console.error(error)
      next(new Error('Unauthorized'))
    }
  })

  server.io.on('connection', (socket) => {
    console.log('New connection:', socket.id)

    socket.on('disconnect', () => {
      if (socket.data.user) {
        removeUserSocket(socket.data.user.id, socket)
      }
    })
  })
}
