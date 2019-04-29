FROM bitnami/dokuwiki:latest
RUN apt-get update
RUN apt-get install -y git