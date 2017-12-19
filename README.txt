Robopages is a website rapid application tool for unix-familiar developers. Any layout is possible. Any content is possible. The source codes are configured to unpack into any Linux /home/username/public_html direcotry. To get this to run properly (in a home directory) you will have to edit conf/dirLStart.conf.ini

If, on the other hand, you install into a real document root, such as /var/www/html/whatever.com,
then no configuration file editing is necessary.

========index.php=======
You will need to edit one line in index.php,  depending on your installation.
If in a /home/username/public_html directory use the index.php line:
$page = new dirCrawler();
If you are in a real DOCUMENT_ROOT use hte index.php line:
$page = new robopages();

The downloadable robopages.zip is often out of date. You might want to try downloading the latest subversin codes instead.  Codes can be swapped in and out without effecting content under the fragments directory.

