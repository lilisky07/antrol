FROM nginx:alpine

# pakai full nginx config
COPY ./docker/production/nginx/nginx.conf /etc/nginx/nginx.conf

WORKDIR /var/www
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
