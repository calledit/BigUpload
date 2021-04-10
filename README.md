# BigUpload 

PHP & javascript library to upload gigantic files without beeing limited by PHP's internal upload limit.

```php
include('inc/bigUpload.php');

@mkdir("/tmp/tmp_upload");
@mkdir("/tmp/Upload_Destination");
$Uploder = new BigUpload("/tmp/tmp_upload/", "/tmp/Upload_Destination/");

if($Uploder->CheckIncomingFile()){
	$Uploder->HandleIncomingFile();
}
```

```html
<form action="?actionold=post-unsupported" method="post" enctype="multipart/form-data">
	<input type="file" name="upload_file" />
	<div>
		<input id="uploadButton" type="button" value="Start Upload" onclick="upload()" />
	  <input type="button" value="Cancel" onclick="abort()" />
  </div>
</form>
<script>
  function OnUploadStatusInfo(status, percent, textinfo){
				console.log('New upload status:', status, 'Percent uploaded:', percent, 'TextInfo:', textinfo);
  }
  
  //setup bigUpload for the file element
  var bigUpload = new bigUpload(document.querySelector('input[type=file]'), OnUploadStatusInfo);
  
  //handle upload and abort buttons
  function upload() {
		bigUpload.fire();
	}
	function abort() {
	  bigUpload.abortFileUpload();
  }
  </script>
```
