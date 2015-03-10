<?php
include('inc/bigUpload.php');

@mkdir("/tmp/tmp_upload");
@mkdir("/tmp/Upload_Destination");
$Uploder = new BigUpload("/tmp/tmp_upload/", "/tmp/Upload_Destination/");

if($Uploder->CheckIncomingFile()){
	$Uploder->HandleIncomingFile();
}




?><!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="style.css">
		<script src="js/bigUpload.js"></script>
	</head>
	<body>
	<body>
		<div class="bigUpload">
			<div class="bigUploadContainer">
				<h1>BigUpload</h1>
				<form action="?actionold=post-unsupported" method="post" enctype="multipart/form-data">
					<input type="file" name="upload_file" />
					<div>
						<input id="uploadButton" type="button" value="Start Upload" onclick="upload()" />
						<input type="button" value="Cancel" onclick="abort()" />
					</div>
				</form>
				<div id="bigUploadProgressBarContainer">
					<div id="bigUploadProgressBarFilled">
					</div>
				</div>
				<div id="bigUploadTimeRemaining"></div>
				<div id="bigUploadResponse"></div>
			</div>
		</div>
		<script>
			var FileInputElement = document.querySelector('input[type=file]')
			function OnUploadStatusInfo(status, percent, textinfo){
				console.log('New upload status:', status, 'Percent uploaded:', percent, 'TextInfo:', textinfo);

				if(status == 'starting'){
					document.querySelector('#bigUploadResponse').textContent = 'Uploading...';
					document.querySelector('#uploadButton').value = 'Pause';
					document.querySelector('#bigUploadProgressBarFilled').style.backgroundColor = 'green';

				}else if(status == 'pause'){
					document.querySelector('#uploadButton').value = 'Resume';

				}else if(status == 'resume'){
					document.querySelector('#uploadButton').value = 'Pause';
					
				}else if(status == 'progress'){
					document.querySelector('#bigUploadProgressBarFilled').style.width = percent + '%'
					document.querySelector('#bigUploadProgressBarFilled').textContent = percent + '%'

				}else if(status == 'timeleft'){
					document.querySelector('#bigUploadTimeRemaining').textContent = textinfo;
				}else if(status == 'server_response'){
					document.querySelector('#bigUploadResponse').textContent = textinfo;
					document.querySelector('#bigUploadTimeRemaining').textContent = '';
				}else if(status == 'error'){
					document.querySelector('#bigUploadResponse').textContent = textinfo;
					document.querySelector('#bigUploadTimeRemaining').textContent = '';
					document.querySelector('#bigUploadProgressBarFilled').style.backgroundColor = 'red';
				}else if(status == 'done'){
					document.querySelector('#uploadButton').value = 'Start Upload';
					document.querySelector('#bigUploadResponse').textContent = 'File uploaded successfully.';
				}else if(status == 'cancel' || status == 'canceling'){
					document.querySelector('#uploadButton').value = 'Start Upload';
					document.querySelector('#bigUploadTimeRemaining').textContent = '';
					document.querySelector('#bigUploadProgressBarFilled').textContent = '';
					document.querySelector('#bigUploadProgressBarFilled').style.width = '0%'
				}
			}
			
			bigUpload = new bigUpload(FileInputElement, OnUploadStatusInfo);
			function upload() {
				bigUpload.fire();
			}
			function abort() {
				bigUpload.abortFileUpload();
			}
		</script>
	</body>
</html>
