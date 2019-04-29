FROM bitnami/dokuwiki:latest
RUN apt-get update
RUN apt-get install -y git
RUN git clone https://github.com/IremBeles/dokuwiki-dockerfile