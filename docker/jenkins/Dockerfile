# Jenkins controller cho lab local: Jenkins LTS + Docker CLI (để build image qua docker.sock của máy).
FROM jenkins/jenkins:lts-jdk17

# Docker CLI tĩnh lấy từ image chính thức (không cài daemon).
COPY --from=docker:27-cli /usr/local/bin/docker /usr/local/bin/docker

# Plugin cần cho Multibranch Pipeline + GitHub.
RUN jenkins-plugin-cli --plugins \
    git \
    workflow-aggregator \
    github-branch-source \
    pipeline-stage-view \
    timestamper \
    ws-cleanup
