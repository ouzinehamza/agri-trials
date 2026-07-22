FROM docker:27-cli

RUN apk add --no-cache bash docker-cli-compose

WORKDIR /deploy

COPY docker/deploy/compose.production.yml /deploy/compose.production.yml
COPY docker/deploy/deploy.sh /usr/local/bin/agri-deploy

RUN chmod +x /usr/local/bin/agri-deploy

ENTRYPOINT ["agri-deploy"]
