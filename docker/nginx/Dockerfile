FROM nginx:1.27-alpine

ARG WWW_ROOT=/var/www/html

RUN apk add --no-cache openssl tzdata && \
    rm -rf /etc/nginx/conf.d/*

COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR ${WWW_ROOT}

EXPOSE 80 443
CMD ["nginx", "-g", "daemon off;"]
