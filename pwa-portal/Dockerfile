# ==============================================================================
# BWS LIS - REACT 19 TS PWA MOBILE PORTAL DOCKERFILE
# ==============================================================================

# --- STAGE 1: Development with Vite Hot Reloading ---
FROM node:22-alpine AS development

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5174

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# --- STAGE 2: Build Static Production PWA Assets ---
FROM node:22-alpine AS builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# --- STAGE 3: Production Nginx Web Server ---
FROM nginx:alpine AS production

COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
