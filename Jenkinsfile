// CI cho iweb-devops: build image -> lint + test song song -> build image prod.
// Mọi lệnh PHP chạy BÊN TRONG image (docker build/run), nên Jenkins chỉ cần Docker CLI,
// không cần cài PHP/Composer và không dính vấn đề bind-mount đường dẫn khi Jenkins chạy trong container.
pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 20, unit: 'MINUTES')
    }

    environment {
        DOCKERFILE = 'docker/php/Dockerfile'
        CI_IMAGE   = "iweb-devops-ci:${env.BUILD_NUMBER}"
        PROD_IMAGE = "iweb-devops:${env.BUILD_NUMBER}"
    }

    stages {
        stage('Build CI image') {
            steps {
                sh 'docker build -f $DOCKERFILE --target dev -t $CI_IMAGE .'
            }
        }

        stage('Lint & Test') {
            parallel {
                stage('Lint (Pint)') {
                    steps {
                        sh 'docker run --rm $CI_IMAGE ./vendor/bin/pint --test'
                    }
                }
                stage('Test (PHPUnit)') {
                    steps {
                        // APP_KEY chỉ dùng tạm cho lần chạy test, không lưu ở đâu cả.
                        sh '''docker run --rm $CI_IMAGE sh -c 'APP_KEY=$(php artisan key:generate --show) ./vendor/bin/phpunit' '''
                    }
                }
            }
        }

        stage('Build prod image') {
            steps {
                sh 'docker build -f $DOCKERFILE --target prod -t $PROD_IMAGE .'
            }
        }
    }

    post {
        always {
            sh 'docker rmi -f $CI_IMAGE || true'
        }
        success {
            echo "OK - image ${env.PROD_IMAGE} đã build (chưa push, CD làm ở bước sau)."
        }
        cleanup {
            cleanWs()
        }
    }
}
