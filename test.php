<?php
ini_set("show_errors", 1);
?>
<form action="https://89.216.112.122/baksa/backend/test.php" method="post" enctype="multipart/form-data">
  Select image to upload:
  <input type="file" name="thumbnail" id="thumbnail">
  <input type="submit" value="Upload Image" name="submit">
</form>

<?php
// $target_dir = "/";
// $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
// $uploadOk = 1;
// $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// // Check if image file is a actual image or fake image
// if(isset($_POST["submit"])) {
//   $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
//   if($check !== false) {
//     echo "File is an image - " . $check["mime"] . ".";
//     $uploadOk = 1;
//   } else {
//     echo "File is not an image.";
//     $uploadOk = 0;
//   }
// }
// echo getcwd();
// echo $_FILES["fileToUpload"]["tmp_name"];
// echo copy($_FILES['fileToUpload']['tmp_name'], getcwd());
// print_r(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], getcwd()));
// echo is_writable(getcwd()."/assets");
// echo getcwd()."/assets";

if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], __DIR__."/assets/".$_FILES['thumbnail']['name'])) {

	echo "The file ". htmlspecialchars( basename( $_FILES["thumbnail"]["name"])). " has been uploaded.";

} else {

	echo "Sorry, there was an error uploading your file.";

}

phpinfo();