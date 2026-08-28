pipeline {
    environment {
        // 실제 노출될 Ingress/Route 도메인에 맞게 필요시 수정
        DOMAIN = 'apps.ocp4.example.com'
        PRJ = "hello-${env.BRANCH_NAME ?: 'main'}-${env.BUILD_NUMBER}"
        APP = 'nodeapp'
    }
    agent {
        node {
            label 'nodejs'
        }
    }
    stages {
        stage('create') {
            steps {
                script {
                    openshift.withCluster() {
                        echo("Create project ${env.PRJ}")
                        openshift.newProject("${env.PRJ}")
                        openshift.withProject("${env.PRJ}") {
                            echo('Grant to developer read access to the project')
                            openshift.raw('policy', 'add-role-to-user', 'view', 'developer')
                            echo("Create app ${env.APP}")
                            openshift.newApp("${env.GIT_URL ?: '.'}#${env.BRANCH_NAME ?: 'main'}", "--strategy=source", "--name=${env.APP}")
                        }
                    }
                }
            }
        }
        stage('build') {
            steps {
                script {
                    openshift.withCluster() {
                        openshift.withProject("${env.PRJ}") {
                            def bc = openshift.selector('bc', "${env.APP}")
                            echo("Wait for build from bc ${env.APP} to finish")
                            timeout(5) {
                                def builds = bc.related('builds').untilEach(1) {
                                    def phase = it.object().status.phase
                                    if (phase == "Failed" || phase == "Error" || phase == "Cancelled") {
                                        error 'OpenShift build failed or was cancelled'
                                    }
                                    return (phase == "Complete")
                                }
                            }
                        }
                    }
                }
            }
        }
        stage('deploy') {
            steps {
                script {
                    openshift.withCluster() {
                        openshift.withProject("${env.PRJ}") {
                            echo("Expose route for service ${env.APP}")
                            openshift.expose("svc/${env.APP}", "--hostname=${env.PRJ}.${env.DOMAIN}")
                            echo("Wait for deployment ${env.APP} to finish")
                            timeout(5) {
                                openshift.selector('deploymentconfig', "${env.APP}").rollout().status()
                            }
                        }
                    }
                }
            }
        }
        stage('test') {
            input {
                message 'About to test the application'
                ok 'Ok'
            }
            steps {
                echo "Check that '${env.PRJ}.${env.DOMAIN}' returns HTTP 200"
                sh "curl -s --fail http://${env.PRJ}.${env.DOMAIN} || true"
            }
        }
    }
    post {
        always {
            script {
                openshift.withCluster() {
                    echo("Delete project ${env.PRJ}")
                    openshift.delete("project/${env.PRJ}")
                }
            }
        }
    }
}
