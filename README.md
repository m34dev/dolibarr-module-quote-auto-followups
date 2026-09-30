# Quote Auto Followups for [Dolibarr ERP & CRM](https://www.dolibarr.org)

## Features

Automatic follow-ups for commercial proposals (quotes) in Dolibarr.

## Module installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolibarr.org](https://www.dolibarr.org).
You can also get a ready-to-use instance in the cloud from https://saas.dolibarr.org

### Download the module

You can download the module package from the official Dolibarr marketplace [Dolistore](https://www.dolistore.com)

### From the ZIP file and GUI interface

If the module is a ready-to-deploy zip file, so with a name `module_quoteautofollowups-1.0.0.zip` (e.g., when downloading it from a marketplace like [Dolistore](https://www.dolistore.com)),
go to menu `Home> Setup> Modules> Deploy external module` and upload the zip file.

<!--

Note: If this screen tells you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the `htdocs/conf/conf.php` file and check that the following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading `//`) and assign the proper value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```
-->

<!--

### From a GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/quoteautofollowups`

```shell
cd ....../custom
git clone git@github.com:gitlogin/quoteautofollowups.git quoteautofollowups
```

-->

### Final steps

Using your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup"> "Modules"
  - You should now be able to find and enable the module

## Translations

Translations can be completed manually by editing files in the module directories under `langs`.

## License

GNU GPLv3
