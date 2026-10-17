# Jenkins controller cho lab local: Jenkins LTS + Docker CLI (để build image qua docker.sock của máy).
FROM jenkins/jenkins:lts-jdk17

# Docker CLI tĩnh lấy từ image chính thức (không cài daemon).
COPY --from=docker:27-cli /usr/local/bin/docker /usr/local/bin/docker

# CD: ssh để vào EC2 deploy, AWS CLI v2 để đăng nhập/push ECR.
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends openssh-client unzip curl \
    && curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-$(uname -m).zip" -o /tmp/awscliv2.zip \
    && unzip -q /tmp/awscliv2.zip -d /tmp \
    && /tmp/aws/install \
    && rm -rf /tmp/aws /tmp/awscliv2.zip /var/lib/apt/lists/*
USER jenkins

# Plugin cần cho Multibranch Pipeline + GitHub.
RUN jenkins-plugin-cli --plugins \
    git \
    workflow-aggregator \
    github-branch-source \
    pipeline-stage-view \
    timestamper \
    ws-cleanup
