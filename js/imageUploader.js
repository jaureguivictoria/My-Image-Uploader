/**
 * Created by Victoria Jáuregui on 29/10/2014.
 */
 
 /* Drag and Drop */
 
 $(document).ready(function() {
	
	// Makes sure the dataTransfer information is sent when we
	// Drop the item in the drop box.
	jQuery.event.props.push('dataTransfer');
    
    showDefaultLabel();
	
	var z = -40;
	// The number of images to display
	
	var errMessage = 0;
	
	// Get all of the data URIs and put them in an array
	var dataArray = [];
	
	// Bind the drop event to the dropzone.
	$('#drop-files').bind('drop', function(e) {
			
		// Stop the default action, which is to redirect the page
		// To the dropped file
		
		var files = e.dataTransfer.files;        
        
        //Valid maxFiles
        if((dataArray.length > 0 && dataArray.length >= maxFiles) || files.length > maxFiles){
            setErrorMessage();
            ++errMessage;
            return false;
        } else {
            
            showDefaultLabel();                         
		
            // Show the upload holder
            $('#uploaded-holder').show();
            
            // For each file
            $.each(files, function(index, file) {
                            
                // Some error messaging
                if (!files[index].type.match('image.*')) {
                    
                    if(errMessage >= 0 && errMessage <= 3) {
                        setErrorMessage();
                        ++errMessage
                    }

                    return false;
                }
                
                // Check length of the total image elements
                
                if($('#dropped-files > .image').length < maxFiles) {
                    // Change position of the upload button so it is centered
                    var imageWidths = ((220 + (40 * $('#dropped-files > .image').length)) / 2) - 20;
                    $('#upload-button').css({'left' : imageWidths+'px', 'display' : 'block'});
                } 
                
                // Start a new instance of FileReader
                var fileReader = new FileReader();
                    
                    // When the filereader loads initiate a function
                    fileReader.onload = (function(file) {
                        
                        return function(e) { 
                            
                            // Push the data URI into an array
                            dataArray.push({name : file.name, value : this.result});
                            
                            // Move each image 40 more pixels across
                            z = z+40;
                            var image = this.result;
                            
                            
                            // Just some grammatical adjustments
                            if(dataArray.length == 1) {
                                $('#upload-button span').html("1 file to be uploaded");
                            } else {
                                $('#upload-button span').html(dataArray.length+" files to be uploaded");
                            }
                            // Place extra files in a list
                            if($('#dropped-files > .image').length < maxFiles) { 
                                // Place the image inside the dropzone                               
                                $('#dropped-files').hide();
                                $("#upload_file_div").hide();
                                $("#drop-files").hide();
                                $('#dropped-files').append('<div class="image" style="left: '+z+'px; background: url('+image+'); background-size: cover;"> </div>');
                                $("#upload-button .upload").click();                                
                            }
                            else {
                                
                                $('#extra-files .number').html('+'+($('#file-list li').length + 1));
                                // Show the extra files dialogue
                                $('#extra-files').show();
                                
                                // Start adding the file name to the file list
                                $('#extra-files #file-list ul').append('<li>'+file.name+'</li>');
                                
                            }
                        }; 
                        
                    })(files[index]);
                    
                // For data URI purposes
                fileReader.readAsDataURL(file);

            }); 
        }	

	});
	
    function setErrorMessage(msg){
        $("#drop-files span#lbl").hide();
        $("#drop-files span#errorsize").hide();
        $("#drop-files span#errormsg").show();
    }
    
    function setSizeErrorMessage(msg){
        $("#drop-files span#lbl").hide();
        $("#drop-files span#errormsg").hide();
        $("#drop-files span#errorsize").show();
    }

    function showDefaultLabel(){
        $("#drop-files span#errormsg").hide();
        $("#drop-files span#errorsize").hide();
        $("#drop-files span#lbl").show();
    }
    
	function restartFiles() {
	
		// This is to set the loading bar back to its default state
		$('#loading-bar .loading-color').css({'width' : '0%'});
		$('#loading').css({'display' : 'none'});
		$('#loading-content').html(' ');
		showDefaultLabel();
		// --------------------------------------------------------
		
		// We need to remove all the images and li elements as
		// appropriate. We'll also make the upload button disappear
		
		$('#upload-button').hide();
		$('#dropped-files > .image').remove();
		$('#extra-files #file-list li').remove();
		$('#extra-files').hide();
		$('#uploaded-holder').hide();
	
		// And finally, empty the array/set z to -40
		dataArray.length = 0;
		z = -40;
		
		return false;
	}

	// Just some styling for the drop file container.
	$('#drop-files').bind('dragenter', function() {
		$(this).css({'box-shadow' : 'inset 0px 0px 20px rgba(0, 0, 0, 0.1)', 'border' : '4px dashed #bb2b2b'});
		return false;
	});
	
	$('#drop-files').bind('drop', function() {
		$(this).css({'box-shadow' : 'none', 'border' : '4px dashed rgba(0,0,0,0.2)'});
		return false;
	});
	
	// For the file list
	$('#extra-files .number').toggle(function() {
		$('#file-list').show();
	}, function() {
		$('#file-list').hide();
	});
	
	$('#dropped-files #upload-button .delete').click(restartFiles);
	
	// Append the localstorage the the uploaded files section
	if(window.localStorage.length > 0) {
		$('#uploaded-files').show();
		for (var t = 0; t < window.localStorage.length; t++) {
			var key = window.localStorage.key(t);
			var value = window.localStorage[key];
			// Append the list items
			if(value != undefined || value != '') {
				$('#uploaded-files').append(value);
			}
		}
	} else {
		$('#uploaded-files').hide();
	}


 /* Resizing */
 "use strict";

    var cropbox = function(options, el){
        var el = el || $(options.imageBox),
            obj =
            {
                state : {},
                ratio : 1,
                options : options,
                imageBox : el,
                thumbBox : el.find(options.thumbBox),
                spinner : el.find(options.spinner),
                image : new Image(),
                getDataURL: function ()
                {
                    var width = this.thumbBox.width(),
                        height = this.thumbBox.height(),
                        canvas = document.createElement("canvas"),
                        dim = el.css('background-position').split(' '),
                        size = el.css('background-size').split(' '),
                        dx = parseInt(dim[0]) - el.width()/2 + width/2,
                        dy = parseInt(dim[1]) - el.height()/2 + height/2,
                        dw = parseInt(size[0]),
                        dh = parseInt(size[1]),
                        sh = parseInt(this.image.height),
                        sw = parseInt(this.image.width);

                    canvas.width = width;
                    canvas.height = height;
                    var context = canvas.getContext("2d");
                    context.drawImage(this.image, 0, 0, sw, sh, dx, dy, dw, dh);
                    var imageData = canvas.toDataURL('image/png');
                    return imageData;
                },
                getBlob: function()
                {
                    var imageData = this.getDataURL();
                    var b64 = imageData.replace('data:image/png;base64,','');
                    var binary = atob(b64);
                    var array = [];
                    for (var i = 0; i < binary.length; i++) {
                        array.push(binary.charCodeAt(i));
                    }
                    return  new Blob([new Uint8Array(array)], {type: 'image/png'});
                },
                zoomIn: function ()
                {
                    this.ratio*=1.1;
                    setBackground();
                },
                zoomOut: function ()
                {
                    this.ratio*=0.9;
                    setBackground();
                },
                setBackground: function()
                {
                    setBackground();
                },
                resetDropzone: function()
                {
                    restartFiles();
                }
            },
            setBackground = function()
            {
                var w =  parseInt(obj.image.width)*obj.ratio;
                var h =  parseInt(obj.image.height)*obj.ratio;

                var pw = (el.width() - w) / 2;
                var ph = (el.height() - h) / 2;
                
                $("#x").val(pw);
                $("#y").val(ph);


                el.css({
                    'background-image': 'url(' + obj.image.src + ')',
                    'background-size': w +'px ' + h + 'px',
                    'background-position': pw + 'px ' + ph + 'px',
                    'background-repeat': 'no-repeat',
                    'background-color' : 'black'
                    });
            },
            imgMouseDown = function(e)
            {
                e.stopImmediatePropagation();

                obj.state.dragable = true;
                obj.state.mouseX = e.clientX;
                obj.state.mouseY = e.clientY;
            },
            imgMouseMove = function(e)
            {
                e.stopImmediatePropagation();

                if (obj.state.dragable)
                {
                    var x = e.clientX - obj.state.mouseX;
                    var y = e.clientY - obj.state.mouseY;

                    var bg = el.css('background-position').split(' ');

                    var bgX = x + parseInt(bg[0]);
                    var bgY = y + parseInt(bg[1]);
                    
                    el.css('background-position', bgX +'px ' + bgY + 'px');

                    obj.state.mouseX = e.clientX;
                    obj.state.mouseY = e.clientY;
                }
            },
            imgMouseUp = function(e)
            {
                e.stopImmediatePropagation();
                obj.state.dragable = false;
            },
            zoomImage = function(e)
            {
                e.originalEvent.wheelDelta > 0 || e.originalEvent.detail < 0 ? obj.ratio*=1.1 : obj.ratio*=0.9;
                setBackground();
            }

        obj.spinner.show();
        obj.image.onload = function() {
            obj.spinner.hide();
            setBackground();

            el.bind('mousedown', imgMouseDown);
            el.bind('mousemove', imgMouseMove);
            $(window).bind('mouseup', imgMouseUp);
            el.bind('mousewheel DOMMouseScroll', zoomImage);
        };
        obj.image.src = options.imgSrc;
        el.on('remove', function(){$(window).unbind('mouseup', imgMouseUp)});

        return obj;
    };

    jQuery.fn.cropbox = function(options){
        return new cropbox(options, this);
    };

});
