# Build Stage 1
FROM node:22 AS build

ARG COMMIT_SHA=unset
ENV COMMIT_SHA=$COMMIT_SHA

WORKDIR /app

RUN corepack enable

#Copiar apenas o package.json. Se não tiver mudado usa layer cache
COPY package.json pnpm-lock.yaml ./

RUN pnpm i

#Copiar ficheiros restantes
COPY . .

RUN pnpm run build

# Build Stage 2
FROM node:22-alpine
WORKDIR /app

# Only `.output` folder is needed from the build stage
COPY --from=build /app/.output/ ./

# Change the port and host
ENV PORT=80
ENV HOST=0.0.0.0

EXPOSE 80

CMD ["node", "/app/server/index.mjs"]
