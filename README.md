# README #

### My Image Uploader Overview ###
This repository hosts a personal project of mine called "My Image Uploader". It was built using Symfony PHP Framework, Doctrine ORM, JavaScript and Twig Templating Engine. It has also some css customizations. I have attached everything necessary in order to review the whole uploader, starting from the views (HTML, Js, CSS) all the way up to the PHP repository. 

## Folder structure
This repository has the following folders:

* example - This is a demo in which I am using the Image Uploader inside an existing project of mine.
* css - Holds some small CSS customizations
* js - Here is the javascript library I built.
* php - Here are the Doctrine entities that handle the image saving in the server.
* html - Contains all the views and it's partial files

## How it works
It consists in a drop zone and a file selector. After you choose the image (by dropping it or selecting it), you may edit and modify the image size (width and height). You are able to crop the image if needed and move it all around the canvas in order to place it wherever you want. Once the edition is finished, you can upload it and then it will be saved in server-side. Otherwise, you are able to choose another image or to remove the current if there is one already uploaded.