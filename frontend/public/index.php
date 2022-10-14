<?php
require_once('../vendor/autoload.php');

use App\AmqpWrapper\Client;
use App\AmqpWrapper\Database;

$db = new Database("../jobs.db");

if(isset($_POST["action"])) 
{
  $jobname = $_POST["jobname"] ?? 'None';
  $jobdesc = $_POST["jobdesc"] ?? 'None';
  $started = date("d-m-Y H:i:s");

  $insert  = $db->query("INSERT INTO jobs(jobname,jobdesc,startedat) VALUES(:jn,:jd,:sa)", 
    array("jn" => $jobname, "jd" => $jobdesc, "sa" => $started)
  );

  if($insert > 0 ) 
  {
    $msgArray = array(
      'id'   => "1",
      'job'  => $jobname,
      'desc' => $jobdesc,
      'time' => $started
    );
    $message = json_encode($msgArray, JSON_UNESCAPED_SLASHES);
    $client  = new Client();
    $client->execute($message);
  
    header('Location: index.php'); exit;
  }
}

if(!empty($_GET["delete"])) 
{
  $id = $_GET["delete"] ?? null;

  $delete = $db->query("DELETE FROM jobs WHERE Id = :id", array("id"=>$id));
  if($delete > 0 ) {
    echo 'Succesfully deleted job from queue!';
  } else {
    header('Location: index.php'); exit;
  }
}

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css?<?php echo time(); ?>" rel="stylesheet">
  </head>
  <body>


    <div class="container">
      
      <div class="d-grid gap-2 d-md-flex justify-content-md-end py-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
          Send New Job
        </button>
      </div>

      <table class="table">
      <thead class="table-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">Job Name</th>
          <th scope="col">Started</th>
          <th scope="col">Completed</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody class="table-group-divider">
        <?php
        $jobs = $db->query("SELECT * FROM jobs");
        foreach($jobs as $job) {
        ?>
        <tr>
          <th scope="row"><?php echo $job[0];?></th>
          <td><?php echo $job["jobname"];?></td>
          <td><?php echo $job["startedAt"];?></td>
          <td><?php echo !empty($job["completedAt"]) ? $job["completedAt"] : '<img src="/images/loading.gif" class="ajax-loading" /> Processing';?></td>
          <td>
            <div class="d-grid gap-2 d-md-block">
              <?php 
              if (!empty($job["completedAt"])) {
                echo '<a href="" class="btn btn-primary btn-sm">view</a> ';
              }
              echo '<a href="?delete='.$job["id"].'" class="btn btn-danger btn-sm">delete</a>';
              ?>
            </div>
          </td>
        </tr>
        <?php
        }
        ?>
      </tbody>
      </table>

    </div>



    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="" method="post" autocomplete="off">

          <div class="modal-body">
            <div class="mb-3">
              <label for="jobname" class="form-label">Job name :</label>
              <input type="text" class="form-control" name="jobname" id="jobname">
            </div>
            <div>
              <label for="jobdesc" class="form-label">Job description :</label>
              <textarea class="form-control" name="jobdesc" id="jobdesc" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Send job</button>
          </div>
          <input type="hidden" name="action" value="new-job" />
          </form>
        </div>
      </div>
    </div>


    <script src="/js/bootstrap.bundle.min.js"></script>
  </body>
</html>