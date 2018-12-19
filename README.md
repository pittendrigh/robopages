# robopages
Hierarchical files based rapid application no-database CMS without layout or complexity limitations

<h1> Robopages Big Picture </h1>

Robopages was developed as a rapid application way to make complex online documentation for online instructional classes. Robopages makes it possible to quickly assemble and continuously edit a complex system of pages with minimal effort. Any layout is possible. Robopages can be used as the framework for websites of arbitrary complexity. 
<br/><br/>
For developers, rapid application is good no matter what. Robopages websites that do not use a database are fast to make and fast to run.  So they can be left as is--and they can be expected to scale well too. But some developers might want to use Robopages as fast way to hack out a new layout. And then, once done, to export that newly developed content and layout to a more cumbersome system like Drupal or Wordpress.
<br/><br/>
The biggest missing features now are an embeddable forum, embeddable shopping cart and/or embeddable blog.  As it is now those features must be added as separate entities whose CSS does its best to make them look like an integral part of the enclosing Robopages site.  But that's a problem for most CMS software systems.  Another feature that would be nice to have is a Zend/Plucene search feature. Right now I use Google Custom Search, which seems to run better as time marches on. There are no database-driven modules for content generation now. But there could be. Forums, blogs and shopping carts have to depend on complex database schemas, but run-of-the-mill page content does not.
<br/><br/>
Robopages uses hierarchical HTML fragment files to create its contents. So you can edit content in place with a text editor or with shell scripts. You can continuously rearrange structure by moving files and directories up or down in the server-side file system.  Links can be generated statically from config files or on the fly--robotically.  
