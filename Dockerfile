FROM bitnami/dokuwiki:latest
RUN apt-get update
RUN apt-get install -y git
RUN git clone https://github.com/IremBeles/dokuwiki-dockerfile
RUN cp dokuwiki-dockerfile/addnewpage opt/bitnami/dokuwiki/lib/plugins/ -r