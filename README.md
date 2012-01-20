Protolus.php
===========

Protolus.php is a web based application development framework written in PHP and using a superset of Smarty(for now) to implement a nesting macro system. This is server half of a complete deployment which also includes client-side rendering, interface generation, etc.

How to use
----------

You need a configuration (a .conf file) which is based on your domain with dots becoming underscores, domain.com becomes domain_com.conf.
You'll need a panel: <something>.panel.tpl, which is a smarty template located in App/Panels.
You'll need a controller: <something>.controller.php, which php script with a predefined $renderer macro object which are placed in App/Contollers.

if you want to do custom routing you need to add lines to routes.conf in the format:

widget/*/view/* = view.php?widget=*type=*

that's the minimum to run it, but there's a huge amount of extra detail you need, which I don't yet have the time to provide. If you are here, it's because you know what you're doing with this already.

    abbey