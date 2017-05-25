# Warpwire Plugin for Moodle
The Warpwire Plugin for Moodle allows your users to insert protected Warpwire assets into any content item for which the WYSIWYG editor is available.

## Install
1. Download the Warpwire Plugin for Moodle 3 by clicking the 'Clone or download' button, select 'Download Zip'
   ***Note:*** You may also use Git or checkout with SVN if you prefer
2. Extract the Zip file.
3. Locate the root directory of your Moodle installation, and upload the ```filter```, ```lib```, ```local```, and ```mod``` folders into that root directory.

## Configure
1. Navigate to '**Site Administration**', click the '**Plugins**' tab, and in the '**Filters**' section, click the '**Manage Filters**' link.
2. Ensure that the 'Warpwire filter' has the 'Active?' setting selected to 'On', and the 'Apply to' setting is set to 'Content'.
3. Navigate to '**Site Administration**', click the '**Plugins**' tab, and in the '**Local plugins**' section, click the '**Warpwire Plugin Configuration**' link.
4. Fill in the values for 'Your Warpwire LTI Launch URL', 'Your Warpwire Consumer Key', and 'Your Warpwire Consumer Secret'.
   ***Note:*** Please contact Warpwire at tech@warpwire.net to request a consumer key and secret for your Warpwire installation.

## Usage
Warpwire content can now be inserted into content items for which the WYSIWYG editor is enabled. Simply click the Warpwire button in the editor, and you will be taken to your Warpwire application, from which content can be embedded into your Moodle instance.
