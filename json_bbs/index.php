<?php
session_start();
$_SESSION["server_token"];

if (empty($_SESSION["server_token"])) {
  $_SESSION["server_token"] = bin2hex(random_bytes(32));
  echo "kara";
}

$jsonfile = "./postdata.json";
if (!file_exists($jsonfile)) {
  $initial_data = json_encode(["item" => []], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  file_put_contents($jsonfile, $initial_data);
}

function post() {
  global $jsonfile;
  $json = json_decode(file_get_contents($jsonfile));

  if ($json === null) {
    $json = new stdClass;
    $json->item = [];
  }

  foreach($_POST as $key => $value) {
    $_POST[$key] = htmlspecialchars($value, ENT_QUOTES, "UTF-8");
  }

  $_POST["content"] = str_replace("/r", "", $_POST["content"]);

  $bbs_data = new stdClass;

  $bbs_data->datetime = $_POST["datetime"];
  $bbs_data->title = $_POST["title"];
  $bbs_data->content = $_POST["content"];

  array_unshift($json->item, $bbs_data);

  usort($json->item, function($a,$b) {
    return strtotime($b->datetime) - strtotime($a->datetime);
  });

  file_put_contents($jsonfile, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

  $_SESSION["server_token"] = bin2hex(random_bytes(32));

  //header("location: ./");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if ($_SESSION["server_token"] === $_POST["posttoken"] && !empty($_POST["content"])) {
    post();
  } else {
    echo "二十送信か投稿が空です。やり直してください。";
  }
}

function disp() {
  global $jsonfile;
  $result = "";

  $disp_data = json_decode(file_get_contents($jsonfile), true);

  if (isset($disp_data['item']) && is_array($disp_data['item'])) {
    if (empty($disp_data["item"])) {
      $result = "<h3>現在、投稿がありません。あなたも参加してみましょう！</h3>";
    } else {
      foreach ($disp_data['item'] as $post) {
        $result .= "<div>";
        $result .= "<h3>" . htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") . "</h3>";
        $result .= "<p><strong>Date:</strong> " . htmlspecialchars($post["datetime"], ENT_QUOTES, "UTF-8") . "</p>";
        $result .= "<p>" . nl2br(htmlspecialchars($post["content"], ENT_QUOTES, "UTF-8")) . "</p>";
        $result .= "</div><hr>";
      }
    }
  } else {
    $result = "システムエラーです。";
  }

  return $result;
}
?>
<!-- This is a basic HTML template that defines the structure of a web page -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- The head section contains the meta information and the title of the page -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My HTML Template</title>
    <style>
form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
form > textarea {
  height: 300px;
}
form > button {
  padding: 5px;
}
.post {
  padding: 15px;
}
    </style>
</head>
<body>
  <form method="post" action="./">
    <input name="title" placeholder="titleを入力" />
    <textarea name="content" placeholder="投稿を入力"></textarea>
    <input name="datetime" value="<?php echo date("Y/M/D/H/i/s"); ?>" type="hidden" />
    <input name="posttoken" value="<?php echo $_SESSION['server_token']; ?>" type="hidden" />
    <button>send</button>
  </form>
  <div class="post">
    <?php echo disp(); ?>
  </div>
</body>
</html>