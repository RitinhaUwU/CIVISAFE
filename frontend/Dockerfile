# Build Stage 1
FROM node:22 AS build
WORKDIR /app

ARG NUXT_PUBLIC_API_BASE
ARG NUXT_PUBLIC_REVERB_APP_KEY
ARG NUXT_PUBLIC_REVERB_HOST
ARG NUXT_PUBLIC_REVERB_PORT
ARG NUXT_PUBLIC_REVERB_SCHEME

ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE
ENV NUXT_PUBLIC_REVERB_APP_KEY=$NUXT_PUBLIC_REVERB_APP_KEY
ENV NUXT_PUBLIC_REVERB_HOST=$NUXT_PUBLIC_REVERB_HOST
ENV NUXT_PUBLIC_REVERB_PORT=$NUXT_PUBLIC_REVERB_PORT
ENV NUXT_PUBLIC_REVERB_SCHEME=$NUXT_PUBLIC_REVERB_SCHEME


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
