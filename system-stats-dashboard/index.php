
<?php
$uptime = shell_exec("uptime -p");
$loads = sys_getloadavg();
$temperature = number_format(
    exec("cat /sys/class/thermal/thermal_zone0/temp") / 1000,
    2
);
$cpuUsages = exec("top -b -n 1 | grep 'Cpu(s)' | awk '{print $2 + $4}'");
$diskFree = disk_free_space("/");
$diskTotal = disk_total_space("/");
$diskUsages = $diskTotal - $diskFree;
$diskUsagesPercentage = number_format(($diskUsages / $diskTotal) * 100, 2);
$ramFree = shell_exec('free -m | grep Mem | awk \'{print $4}\'') * 1048576; //in bytes
$ramTotal = shell_exec('free -m | grep Mem | awk \'{print $2}\'') * 1048576; //in bytes
$ramUsages = $ramTotal - $ramFree;
$ramUsagesPercentage = number_format(($ramUsages / $ramTotal) * 100, 2);
$privateIPs = trim(shell_exec("hostname -I"));
$topRamProcess = shell_exec("ps aux --sort=-%mem | head -n 5");
$topCpuProcess = shell_exec("ps aux --sort=-%cpu | head -n 5");
$uptime = shell_exec("uptime -p");
$activeUsers = shell_exec("who | wc -l");
$numProcesses = shell_exec("ps aux | wc -l");
$systemArchitecture = shell_exec("uname -m");
$activeServices = shell_exec("service --status-all");

function getColour($currentVal, $maxVal = 100)
{
    $p = ($currentVal / $maxVal) * 100;
    if ($p < 50) {
        return "success";
    }

    if ($p < 60) {
        return "info";
    }

    if ($p < 75) {
        return "warning";
    }

    return "danger";
}

function formatBytes($bytes, $precision = 2)
{
    $units = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . " " . $units[$pow];
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Stats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
  </head>
  <body class="container">
    <div class="col-lg-7 px-5 py-3 shadow shadow-lg rounded mt-5 mx-auto">
 <form>
 <div class="p-3">
 <label>Check Custom Port</label>
  <input autocomplete="off"  class="form-control my-2" name="ports" value="<?= $_GET[
      "ports"
  ] ?>">
  <small>Please Enter Port to check (1-65353) seprated by comma</small>
  <button class="btn btn-primary float-end" type="submit">Check</button>
  <br/>
  <br/>
</div>

</form>
    <table class="table table-striped">
  <thead class="table-dark">
    <tr>
      <th scope="col">Name</th>
      <th scope="col" class="text-center">Port</th>
      <th scope="col" class="text-center">Status</th>
    </tr>
  </thead>
  <tbody>
<?php
$servers = [
    [
        "host" => "localhost",
        "port" => 21,
        "name" => "FTP",
    ],
    [
        "host" => "localhost",
        "port" => 22,
        "name" => "SSH",
    ],
    [
        "host" => "google.com",
        "port" => 80,
        "name" => "Internet Connection",
    ],
    [
        "host" => "localhost",
        "port" => 80,
        "name" => "Web Server",
    ],
    [
        "host" => "localhost",
        "port" => 8080,
        "name" => "Web Server",
    ],
    [
        "host" => "localhost",
        "port" => 3306,
        "name" => "MYSQL",
    ],
];
if (isset($_GET["ports"])) {
    $customPorts = $_GET["ports"];
    $customPortArr = explode(",", $customPorts);
    foreach ($customPortArr as $port) {
        $port = (int) trim($port);
        if ($port > 65353 || $port < 1) {
            continue;
        }

        $newPorts = [
            "host" => "localhost",
            "port" => $port,
            "name" => "Custom",
        ];
        array_push($servers, $newPorts);
    }
}

foreach ($servers as $server) {
    $socket = @fsockopen(
        $server["host"],
        $server["port"],
        $errno,
        $errstr,
        1
    ); ?>


<tr>
      <td><?= $server["name"] ?></td>
      <td class="text-center"><?= $server["port"] ?></td>
<?php if ($socket) {
    fclose($socket); ?>
      <td class="table-success text-center">Up</td>
    <?php
} else {
     ?>
      <td class="table-danger text-center">Down</td>
<?php
} ?>
    </tr>
<?php
}
?>
  </tbody>
</table>

<h3 class="mt-5">System Info</h3>
<table class="table table-striped">
<tr>
      <th scope="col">Uptime</th>
      <th><?= $uptime ?></th>
    </tr><tr>
      <th scope="col">System Architecture</th>
      <th><?= $systemArchitecture ?></th>
    </tr>
    <tr>
      <th scope="col">Temperature</th>
      <th class="table-<?= getColour(
          $temperature,
          80
      ) ?>"><?= $temperature ?> °C</th>
    </tr>
    <tr>
      <th scope="col">System IP</th>
      <th>
      <?= str_replace(" ", " ,<br>", $privateIPs) ?> </th>
    </tr>
    <tr>
      <th scope="col">Active SSH Connection</th>
      <th><?= $activeUsers ?></th>
    </tr>
    <tr>
      <th scope="col">Active Process</th>
      <th><?= $numProcesses ?></th>
    </tr>
    <tr>
      <th scope="col">System Loads</th>
      <th>
        <?php foreach ($loads as $singleLoad) {
            echo "<span class='badge bg-secondary mx-2'>" .
                number_format($singleLoad, 4) .
                " </span>";
        } ?>
</th>
    </tr>
    <tr>
      <th scope="col">Disk Usages</th>
      <th>
      <?= formatBytes($diskUsages) ?>/<?= formatBytes($diskTotal) ?>
      <div class="progress bg-secondary" role="progressbar" aria-valuenow="<?= $diskUsagesPercentage ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= getColour(
            $diskUsagesPercentage
        ) ?>" style="width: <?= $diskUsagesPercentage ?>%"><?= $diskUsagesPercentage ?>%</div>
      </div>

    </th>
    </tr>
    <tr>
      <th scope="col">Memory Usages</th>
      <th>
        <?= formatBytes($ramUsages) ?>/<?= formatBytes($ramTotal) ?>

      <div class="progress bg-secondary" role="progressbar" aria-valuenow="<?= $ramUsagesPercentage ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= getColour(
            $ramUsagesPercentage
        ) ?>" style="width: <?= $ramUsagesPercentage ?>%"><?= $ramUsagesPercentage ?>%</div>
      </div>

     </th>

    <tr>
      <th scope="col">CPU Usages</th>
      <th>
       <?= $cpuUsages ?> %

      <div class="progress bg-secondary" role="progressbar" aria-valuenow="<?= $cpuUsages ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-<?= getColour(
            $cpuUsages
        ) ?>" style="width: <?= $cpuUsages ?>%"><?= $cpuUsages ?>%</div>
      </div>

     </th>
    </tr>
</table>



<h3 class="mt-5">Active SSH Users</h3>
<table class="table table-striped">
        <tr>
            <th>Username</th>
            <th>Terminal</th>
            <th>Date & Time</th>
            <th>IP</th>
        </tr>
<?php
$command = "";
$output = shell_exec("who");
$lines = explode("\n", trim($output));

foreach ($lines as $line) {
    $parts = preg_split("/\s+/", $line);
    if (count($parts) >= 4) {
        $username = $parts[0];
        $terminal = $parts[1];
        $datetime = $parts[2] . " " . $parts[3] . " " . $parts[4];
        $ip = $parts[5];
        echo '<tr>
                <td>' .
            htmlspecialchars($username) .
            '</td>
                <td>' .
            htmlspecialchars($terminal) .
            '</td>
                <td>' .
            htmlspecialchars($datetime) .
            '</td>
                <td>' .
            $ip .
            '</td>
            </tr>';
    }
}
?>

</table>

<h3 class="mt-5">All Services</h3>
<table class="table">
<?php
$activeServicesArray = explode("\n", trim($activeServices));
foreach ($activeServicesArray as $line) {
    $line = trim($line);
    if ($line[2] == "+") {
        $colour = "table-success";
    } else {
        $colour = "table-secondary";
    }
    echo "<tr class='$colour'><td>" . htmlspecialchars($line) . "</td></tr>";
}
?>
</table>


<h3 class="mt-5">Network Usages</h3>
<small>
  please install vnstat on system using  <code bash>sudo apt-get install vnstat</code>
</small>
<br/>
<?php
$traffic_arr = [];
exec("vnstat -d" . escapeshellarg($_GET["showtraffic"]), $traffic_arr);
$traffic = implode("\n", $traffic_arr);
echo "<pre>" . $traffic . "</pre>";

$traffic_arrHourly = [];
exec("vnstat -h" . escapeshellarg($_GET["showtraffic"]), $traffic_arrHourly);
$traffic_arrHourly = implode("\n", $traffic_arrHourly);
echo "<pre>" . $traffic_arrHourly . "</pre>";
?>

<h3 class="mt-5">Top RAM Users</h3>
<pre class="pb-4 px-3 bg-dark-subtle"><?= $topRamProcess ?></pre>


<h3 class="mt-5">Top CPU Users</h3>
<pre class="pb-4 px-3 bg-dark-subtle"><?= $topCpuProcess ?></pre>

    </div>
  </body>
</html>