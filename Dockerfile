FROM bitnami/dokuwiki:latest
RUN apt-get update
RUN apt-get install -y git
RUN git clone https://github.com/IremBeles/dokuwiki-dockerfile.git
# RUN cp dokuwiki-dockerfile/captcha opt/bitnami/dokuwiki/lib/plugins/ -r
RUN cp dokuwiki-dockerfile/addnewpage opt/bitnami/dokuwiki/lib/plugins/ -r
RUN cp dokuwiki-dockerfile/cloud/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/discussion/ opt/bitnami/dokuwiki/lib/discussion -r