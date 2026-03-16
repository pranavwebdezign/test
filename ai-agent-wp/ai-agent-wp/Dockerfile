# ============================================================
# Stage 1: Build React / Vite App
# ============================================================
FROM node:20-alpine AS build

WORKDIR /app

# Copy package files first (Docker layer cache friendly)
COPY package.json package-lock.json ./

# Clean install with all optional platform-specific deps (e.g. Rollup Linux binary)
RUN npm install --include=optional

# Copy source and build
COPY . .
RUN npm run build

# ============================================================
# Stage 2: Serve with Nginx
# ============================================================
FROM nginx:1.25-alpine

LABEL maintainer="AI Agent Team"
LABEL description="AI Agent WP - React/Vite Frontend"

# Remove default Nginx config
RUN rm -rf /etc/nginx/conf.d/default.conf

# Copy built assets from Stage 1
COPY --from=build /app/dist /usr/share/nginx/html

# Copy custom Nginx config
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
