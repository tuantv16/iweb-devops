// CI/CD cho iweb-devops: build image -> lint + test song song -> build image prod
// -> (chỉ nhánh develop) push lên ECR -> deploy lên staging (EC2) -> smoke test.
// Mọi lệnh PHP chạy BÊN TRONG image (docker build/run), nên Jenkins chỉ cần Docker CLI,
// không cần cài PHP/Composer và không dính vấn đề bind-mount đường dẫn khi Jenkins chạy trong container.
//
// Credential cần có trong Jenkins:
//   aws-ecr-push : Username with password = AWS access key id / secret (IAM user chỉ có quyền push ECR)
//   staging-ssh  : SSH Username with private key (user ubuntu của EC2 staging)
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

        AWS_REGION   = 'ap-southeast-1'
        ECR_REGISTRY = '707243280124.dkr.ecr.ap-southeast-1.amazonaws.com'
        // Tag theo commit để biết server đang chạy code nào và quay lui dễ.
        ECR_IMAGE    = "${ECR_REGISTRY}/iweb-devops:develop-${env.GIT_COMMIT ? env.GIT_COMMIT.take(7) : env.BUILD_NUMBER}"
        STAGING_HOST = '54.255.117.64'
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

        // Merge PR vào develop -> Jenkins build nhánh develop -> hai stage dưới đây mới chạy.
        stage('Push to ECR') {
            when { branch 'develop' }
            steps {
                withCredentials([usernamePassword(credentialsId: 'aws-ecr-push',
                                                  usernameVariable: 'AWS_ACCESS_KEY_ID',
                                                  passwordVariable: 'AWS_SECRET_ACCESS_KEY')]) {
                    // Config docker tạm để không để lại token ECR trong Jenkins home.
                    sh '''
                        export DOCKER_CONFIG=$(mktemp -d)
                        trap 'rm -rf "$DOCKER_CONFIG"' EXIT
                        aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REGISTRY
                        docker tag $PROD_IMAGE $ECR_IMAGE
                        docker push $ECR_IMAGE
                    '''
                }
            }
        }

        stage('Deploy to staging') {
            when { branch 'develop' }
            steps {
                withCredentials([sshUserPrivateKey(credentialsId: 'staging-ssh',
                                                   keyFileVariable: 'SSH_KEY',
                                                   usernameVariable: 'SSH_USER')]) {
                    sh '''
                        SSH_OPTS="-i $SSH_KEY -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=$WORKSPACE/.known_hosts"
                        scp $SSH_OPTS docker-compose.staging.yml $SSH_USER@$STAGING_HOST:~/docker-compose.staging.yml
                        ssh $SSH_OPTS $SSH_USER@$STAGING_HOST "bash -s -- $ECR_IMAGE" < deploy/staging-deploy.sh
                    '''
                }
            }
        }

        stage('Smoke test') {
            when { branch 'develop' }
            steps {
                sh 'curl -fsS --retry 10 --retry-delay 3 --retry-all-errors -o /dev/null -w "staging HTTP %{http_code}\\n" http://$STAGING_HOST/'
            }
        }
    }

    post {
        always {
            sh 'docker rmi -f $CI_IMAGE || true'
        }
        success {
            echo "OK - image ${env.PROD_IMAGE} đã build. Nhánh develop sẽ được push ECR và deploy staging: http://${env.STAGING_HOST}/"
        }
        cleanup {
            cleanWs()
        }
    }
}
