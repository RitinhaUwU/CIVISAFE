import { serverStart } from "./server.js"

const WEBSOCKET_PORT = process.env.WESOCKET_PORT || 3001
const REDIS_HOST = process.env.WEBSOCKET_REDIS_HOST || "127.0.0.1"
const REDIS_PORT = process.env.WEBSOCKET_REDIS_PORT || 6379
const BACKEND_API_URL = process.env.WEBSOCKET_BACKEND_API_URL || "http://localhost:8000/api/v1"

console.log("Current Settings:")
console.log(`Redis on ${REDIS_HOST}:${REDIS_PORT}`)
console.log(`Websocket on port ${WEBSOCKET_PORT}`)
console.log(`Backend URL: ${BACKEND_API_URL}`)

serverStart(WEBSOCKET_PORT, REDIS_HOST, REDIS_PORT, BACKEND_API_URL)

console.log("Waiting for connections...")