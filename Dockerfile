FROM bitnami/dokuwiki:0.20180422.201805030840-debian-9-r124
RUN apt-get update
RUN apt-get install -y git
RUN sed -i "s/interwiki'] = ''/interwiki'] = 'extern_tab'/g" opt/bitnami/dokuwiki/conf/dokuwiki.php
RUN sed -i "s/windows']   = ''/windows']   = 'extern_tab'/g" opt/bitnami/dokuwiki/conf/dokuwiki.php
RUN sed -i "s/media']     = ''/media']     = 'extern_tab'/g" opt/bitnami/dokuwiki/conf/dokuwiki.php
RUN git clone https://github.com/IremBeles/dokuwiki-dockerfile.git
RUN cp dokuwiki-dockerfile/captcha opt/bitnami/dokuwiki/lib/plugins/ -r
RUN cp dokuwiki-dockerfile/backup opt/bitnami/dokuwiki/lib/plugins/ -r
RUN cp dokuwiki-dockerfile/addnewpage opt/bitnami/dokuwiki/lib/plugins/ -r
RUN cp dokuwiki-dockerfile/cloud/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/discussion/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/dw2pdf/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/loglog/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/medialist/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/move/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/s5/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/tag/ opt/bitnami/dokuwiki/lib/plugins -r
RUN cp dokuwiki-dockerfile/tokenbucketauth/ opt/bitnami/dokuwiki/lib/plugins -r
