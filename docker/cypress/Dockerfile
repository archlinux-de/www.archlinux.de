FROM archlinux AS cypress-run

RUN pacman -Syu --noconfirm --cachedir /tmp/pacman-cache --needed \
    nodejs npm xorg-server-xvfb nss gtk3 alsa-lib

RUN useradd -m -U -s /usr/bin/nologin cypress
USER cypress
RUN npm config set prefix=/home/cypress/npm
ENV PATH=/home/cypress/npm/bin:$PATH

RUN npm install -g cypress@~11
RUN cypress verify

USER root
RUN pacman -Syu --noconfirm --cachedir /tmp/pacman-cache --needed \
    chromium firefox
USER cypress

ENTRYPOINT ["cypress", "run"]

FROM cypress-run AS cypress-open

USER root
RUN pacman -Syu --noconfirm --cachedir /tmp/pacman-cache --needed \
    icewm

USER cypress
RUN mkdir /home/cypress/.icewm
COPY icewm-preferences.ini /home/cypress/.icewm/preferences
COPY --chmod=755 cypress-open.sh /home/cypress/cypress-open

ENTRYPOINT ["/home/cypress/cypress-open"]
