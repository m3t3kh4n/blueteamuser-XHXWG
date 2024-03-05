resource "aws_ecs_cluster" "cluster" {
  name = "ecs-lab-cluster"
  
  tags = {
   name = "ecs-cluster-name"
   }
   
  }

resource "aws_ecs_task_definition" "task_definition" {
  container_definitions    = "${data.template_file.task_definition_json.rendered}"
  family                   = "terraform-task-definition"
  memory                   = "512"
  cpu                      = "512"
  requires_compatibilities = ["EC2"]  
  task_role_arn            = aws_iam_role.ecs-task-role.arn
  pid_mode = "host"
  volume {
    name      = "docker_sock"
    host_path = "/var/run/docker.sock"
  }
} 

data "template_file" "task_definition_json" {
  template = "${file("task_definition.json")}"
}

resource "aws_ecs_service" "worker" {
  name            = "ecs_service_worker"
  cluster         = aws_ecs_cluster.cluster.id
  task_definition = aws_ecs_task_definition.task_definition.arn
  desired_count   = 1
}