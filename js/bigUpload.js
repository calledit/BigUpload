function bigUpload (inputField, OnUploadStatus) {

	//These are the main config variables and should be able to take care of most of the customization
	this.settings = {
		//The file input
		'inputField': inputField,

		//The form with the file upload.
		//This should be a valid html form so there is a fallback for unsupported browsers
		'form': inputField.form,

		//A function that gets called when there is upload status info
		'OnUploadStatus': OnUploadStatus,

		//Path to the php script for handling the uploads
		'scriptPath': inputField.form.action,

		//Size of chunks to upload (in bytes)
		//Default: 1MB
		'chunkSize': 1000000,

		//Max file size allowed
		//Default: 2GB
		'maxFileSize': 214748364800
	};

	//Fix the url so that ? are not used twice
	if(this.settings.scriptPath.indexOf('?') == -1){
		this.settings.scriptPath += '?';
	}else{
		this.settings.scriptPath += '&';
	}

	//Upload specific variables
	this.uploadData = {
		'uploadStarted': false,
		'file': false,
		'numberOfChunks': 0,
		'aborted': false,
		'paused': false,
		'pauseChunk': 0,
		'key': 0,
		'timeStart': 0,
		'totalTime': 0
	};

	parent = this;

	//Quick function for accessing objects
	this.$ = function(id) {
		return document.getElementById(id);
	};

	//Resets all the upload specific data before a new upload
	this.resetKey = function() {
			this.uploadData = {
				'uploadStarted': false,
				'file': false,
				'numberOfChunks': 0,
				'aborted': false,
				'paused': false,
				'pauseChunk': 0,
				'key': 0,
				'timeStart': 0,
				'totalTime': 0
			};
		};

	//Inital method called
	//Determines whether to begin/pause/resume an upload based on whether or not one is already in progress
	this.fire = function() {
		if(this.uploadData.uploadStarted === true && this.uploadData.paused === false) {
			this.pauseUpload();
		}
		else if(this.uploadData.uploadStarted === true && this.uploadData.paused === true) {
			this.resumeUpload();
		}
		else {
			this.processFiles();
		}

	};

	//Initial upload method
	//Pulls the size of the file being uploaded and calculated the number of chunks, then calls the recursive upload method
	this.processFiles = function() {

		//If the user is using an unsupported browser, the form just submits as a regular form
		if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
			this.$(this.settings.form).submit();
			return;
		}

		//Reset the upload-specific variables
		this.resetKey();
		this.uploadData.uploadStarted = true;

		//Inform about upload starting
		OnUploadStatus('starting', 0);

		//Alias the file input object to this.uploadData
		this.uploadData.file = this.settings.inputField.files[0];

		//Check the filesize. Obviously this is not very secure, so it has another check in inc/bigUpload.php
		//But this should be good enough to catch any immediate errors
		var fileSize = this.uploadData.file.size;
		if(fileSize > this.settings.maxFileSize) {
			this.printResponse('The file you have chosen is too large.', true);
			return;
		}

		//Calculate the total number of file chunks
		this.uploadData.numberOfChunks = Math.ceil(fileSize / this.settings.chunkSize);

		//Start the upload
		this.sendFile(0);
	};

	//Main upload method
	this.sendFile = function (chunk) {

		//Set the time for the beginning of the upload, used for calculating time remaining
		this.uploadData.timeStart = new Date().getTime();

		//Check if the upload has been cancelled by the user
		if(this.uploadData.aborted === true) {
			parent.resetKey();
			OnUploadStatus('cancel', 0);
			return;
		}

		//Check if the upload has been paused by the user
		if(this.uploadData.paused === true) {
			this.uploadData.pauseChunk = chunk;
			this.printResponse('Upload paused.', false);
			return;
		}

		//Set the byte to start uploading from and the byte to end uploading at
		var start = chunk * this.settings.chunkSize;
		var stop = start + this.settings.chunkSize;

		//Initialize a new FileReader object
		var reader = new FileReader();

		reader.onloadend = function(evt) {

			//Build the AJAX request
			//
			//this.uploadData.key is the temporary filename
			//If the server sees it as 0 it will generate a new filename and pass it back in the JSON object
			//this.uploadData.key is then populated with the filename to use for subsequent requests
			//When this method sends a valid filename (i.e. key != 0), the server will just append the data being sent to that file.
			xhr = new XMLHttpRequest();
			xhr.open("POST", parent.settings.scriptPath + 'action=upload&key=' + parent.uploadData.key, true);
			xhr.setRequestHeader("Content-type", "application/x-www-form-raw-data");

			xhr.onreadystatechange = function() {
				if(xhr.readyState == 4) {
					var response = JSON.parse(xhr.response);

					//If there's an error, call the error method and break the loop
					if(response.errorStatus !== 0 || xhr.status != 200) {
						parent.printResponse(response.errorText, true);
						return;
					}

					//If it's the first chunk, set this.uploadData.key to the server response (see above)
					if(chunk === 0 || parent.uploadData.key === 0) {
						parent.uploadData.key = response.key;
					}

					//If the file isn't done uploading, update the progress bar and run this.sendFile again for the next chunk
					if(chunk < parent.uploadData.numberOfChunks) {
						parent.progressUpdate(chunk + 1);
						parent.sendFile(chunk + 1);
					}
					//If the file is complete uploaded, instantiate the finalizing method
					else {
						parent.sendFileData();
					}

				}

			};

			//Send the file chunk
			xhr.send(blob);
		};

		//Slice the file into the desired chunk
		//This is the core of the script. Everything else is just fluff.
		var blob = this.uploadData.file.slice(start, stop);
		reader.readAsBinaryString(blob);
	};

	//This method is for whatever housekeeping work needs to be completed after the file is finished uploading.
	//As it's setup now, it passes along the original filename to the server and the server renames the file and removes it form the temp directory.
	//This function could also pass things like this.uploadData.file.type for the mime-type (although it would be more accurate to use php for that)
	//Or it could pass along user information or something like that, depending on the context of the application.
	this.sendFileData = function() {
		var data = 'key=' + this.uploadData.key + '&name=' + this.uploadData.file.name;
		xhr = new XMLHttpRequest();
		xhr.open("POST", parent.settings.scriptPath + 'action=finish', true);
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

		xhr.onreadystatechange = function() {
				if(xhr.readyState == 4) {
					var response = JSON.parse(xhr.response);

					//If there's an error, call the error method
					if(response.errorStatus !== 0 || xhr.status != 200) {
						parent.printResponse(response.errorText, true);
						return;
					}

					//Reset the upload-specific data so we can process another upload
					parent.resetKey();

					//Report that the upload is done
					OnUploadStatus('done', 100);
				}
			};

		//Send the reques
		xhr.send(data);
	};

	//This method cancels the upload of a file.
	//It sets this.uploadData.aborted to true, which stops the recursive upload script.
	//The server then removes the incomplete file from the temp directory, and the html displays an error message.
	this.abortFileUpload = function() {
		this.uploadData.aborted = true;
		var data = 'key=' + this.uploadData.key;
		xhr = new XMLHttpRequest();
		xhr.open("POST", this.settings.scriptPath + 'action=abort', true);
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

		xhr.onreadystatechange = function() {
				if(xhr.readyState == 4) {
					var response = JSON.parse(xhr.response);

					//If there's an error, call the error method.
					if(response.errorStatus !== 0 || xhr.status != 200) {
						parent.printResponse(response.errorText, true);
						return;
					}
					parent.printResponse('File upload was cancelled.', true);
					OnUploadStatus('canceling', 0);
				}

			};

		//Send the request
		xhr.send(data);
	};

	//Pause the upload
	//Sets this.uploadData.paused to true, which breaks the upload loop.
	//The current chunk is still stored in this.uploadData.pauseChunk, so the upload can later be resumed.
	//In a production environment, you might want to have a cron job to clean up files that have been paused and never resumed,
	//because this method won't delete the file from the temp directory if the user pauses and then leaves the page.
	this.pauseUpload = function() {
		this.uploadData.paused = true;
		OnUploadStatus('pause', 0);
	};

	//Resume the upload
	//Undoes the doings of this.pauseUpload and then re-enters the loop at the last chunk uploaded
	this.resumeUpload = function() {
		this.uploadData.paused = false;
		OnUploadStatus('resume', 0);
		this.sendFile(this.uploadData.pauseChunk);
	};

	//This method updates a simple progress bar by calculating the percentage of chunks uploaded.
	//Also includes a method to calculate the time remaining by taking the average time to upload individual chunks
	//and multiplying it by the number of chunks remaining.
	this.progressUpdate = function(progress) {

		var percent = Math.ceil((progress / this.uploadData.numberOfChunks) * 100);
		//Inform about upload starting
		OnUploadStatus('progress', percent);

		//Calculate the estimated time remaining
		//Only run this every five chunks, otherwise the time remaining jumps all over the place (see: http://xkcd.com/612/)
		if(progress % 5 === 0) {

			//Calculate the total time for all of the chunks uploaded so far
			this.uploadData.totalTime += (new Date().getTime() - this.uploadData.timeStart);
			//console.log(this.uploadData.totalTime);

			//Estimate the time remaining by finding the average time per chunk upload and
			//multiplying it by the number of chunks remaining, then convert into seconds
			var timeLeft = Math.ceil((this.uploadData.totalTime / progress) * (this.uploadData.numberOfChunks - progress) / 100);
			//console.log(Math.ceil(((this.uploadData.totalTime / progress) * this.settings.chunkSize) / 1024) + 'kb/s');

			OnUploadStatus('timeleft', percent, timeLeft + ' seconds remaining');
		}
	};

	//Simple response/error handler
	this.printResponse = function(responseText, error) {
		if(error === true) {
			OnUploadStatus('error', 0, responseText);
		}else{
			OnUploadStatus('server_response', 0, responseText);
		}
	};
}
