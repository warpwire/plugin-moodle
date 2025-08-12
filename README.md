# Warpwire Plugin for Moodle
The Warpwire Plugin for Moodle allows your users to insert protected Warpwire assets into any content item for which the WYSIWYG editor is available.

## Install
| Moodle Version |                            Plugin version                               |
|----------------|-------------------------------------------------------------------------|
| 4.0 - 4.4      | [4.2.1](https://github.com/warpwire/plugin-moodle/releases/tag/4.2.1)   |
| 4.5+           | [5.1.0](https://github.com/warpwire/plugin-moodle/releases/tag/5.1.0)   |

1. Use the table above to match your Moodle version with the correct plugin version.
2. Download the five plugin zip files associated with the version you need. The zip files will be in the **Assets** section.
3. Navigate to **Site Administration**, click the **Plugins** item, and click the **Install Plugins** link.
4. Drag or select for upload the `local` plugin Zip file and click **Install Plugin from Zip FIle**.
5. Click **Validate** on the next page.
6. Click **Continue** on the next page.
7. Click **Upgrade Moodle** on the next page.
8. Repeat steps 2 through 6 for the remaining plugin Zip files in this order: `filter`, `editor_atto`, `tiny`, `mod`.

## Configure
1. Navigate to **Site Administration**, click the **Plugins** tab, and in the **Filters** section, click the **Manage Filters** link.

![Moodle: Plugins](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-plugins.png)

![Moodle: Plugins: Filters](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-plugin-filters.png)

![Moodle: Plugins: Filters: Manage Filters](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-manage-filters.png)

2. Ensure that the 'Warpwire filter' has the 'Active?' setting selected to 'On', and the 'Apply to' setting is set to 'Content'.
3. Navigate to **Site Administration**, click the **Plugins** tab, and in the **Local plugins** section, click the **Warpwire Plugin Configuration** link.

![Moodle: Local Plugins](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-local-plugins.png)

![Moodle: Warpwire Plugin Configuration](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-ww-config.png)

4. Fill in the values for 'Your Warpwire LTI Launch URL', 'Your Warpwire Consumer Key', and 'Your Warpwire Consumer Secret'.
   ***Note:*** Please contact Warpwire at tech@warpwire.net to request a consumer key and secret for your Warpwire installation.

## Usage
Warpwire content can now be inserted into content items for which the WYSIWYG editor is enabled. Simply click the Warpwire button in the editor, and you will be taken to your Warpwire application, from which content can be embedded into your Moodle instance.

![Moodle: Embed via Test Editor](https://github.com/warpwire/warpwire-moodle-3/blob/master/moodle-embed.jpg)
