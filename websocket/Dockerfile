## Stage 1: Build Stage
FROM node:23 AS builder
WORKDIR /app

#Copiar apenas o package.json. Se não tiver mudado usa layer cache
COPY package*.json ./
RUN npm install --only=production

#Copiar ficheiros restantes
COPY . .

## Stage 2: Production
FROM node:23-alpine AS production
WORKDIR /app

#Set env to prod
ENV NODE_ENV=production

#Copiar só os ficheiros necessários do build stage para a img de prod
COPY --from=builder /app ./

#Chown to node
RUN chown -R node:node /app

#trocar para user node (não root)
USER node

EXPOSE 3001
CMD [ "node", "index.js" ]
