#!/bin/bash

function install_docker() {
  local version=$1

  case $version in
    1.10.0 ) install_1.10.0;;
    * ) echo "${version} not available. CircleCI docker may not be properly installed"
  esac
}

function install_1.10.0() {
  sudo curl -L -o /usr/bin/docker 'https://s3-external-1.amazonaws.com/circle-downloads/docker-1.10.0-circleci'
  echo 'DOCKER_OPTS="-s btrfs -D -e lxc"' | sudo tee /etc/default/docker
  echo 'env container=yes' | sudo tee -a /etc/init/docker.conf
}

install_docker $1
