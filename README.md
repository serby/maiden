# Maiden - A PHP Build Tool

Maiden is a simple build system for writing build tasks and other common development tasks.

## Why another build tool? What is wrong with make, rake, phing, ant etc.

At Clock we live and breath PHP (And node.js), we love it! Every technical member of staff has a solid understanding of PHP and is setup to code PHP.
All the web sites/applications, all the tools, as much as possible we code in PHP.

Previously we have used PHING to build, install, deploy, etc, but when your targets get more advanced, writing and debugging
becomes really hard and really time consuming. This approach didn't make any sense! Everyone has the tools to write and debug PHP and yet we were
programming in XML. Also our QA tools couldn't be used to ensure that our PHING/ANT build targets were valid and of a decent quality.

Maiden aims to keep all of your project code in a common language. If you have PHP tools and classes that your project uses then your build targets
can also take advantage of these. You can even use PHPUnit to test your build scripts.

Another advantage of Maiden is that your build files benefit from all the power of PHPs language constructs; abstraction, inheritance, namespaces,
the std library, you can use them all. All this means you write less code, but produce better quality and more powerful build targets.

## Installation

     cd /usr/share/php
     sudo git clone git://github.com/serby/maiden.git
     cd maiden
     sudo git submodule init
     sudo git submodule update
     sudo ./maiden install

## Usage

     maiden 						# List all targets in ./Maiden.php with descriptions
     maiden -h 						# Show help
     maiden -l 						# List all targets in ./Maiden.php with descriptions
     maiden -b 						# List all targets in ./Maiden.php without descriptions
     maiden <target>				# Runs a <target>
     maiden -q <target>				# Runs a <target> and supresses output
     maiden -v <target>				# Runs a <target> with verbose output

## zsh Autocomplete

### Native zsh

Add the following to your ~/.zshrc (or ~/.zshrc.local):

     fpath=(/usr/share/php/maiden $fpath)
     autoload -U compinit
     compinit -i
     source /usr/share/php/maiden/maiden.plugin.zsh

Start / restart zsh

## Credits

[Paul Serby](https://github.com/serby/)

[Steven Jack](https://github.com/stevenjack/)

[Luke Wilde](https://github.com/lukewilde/)


## Licence
Licenced under the [New BSD License](http://opensource.org/licenses/bsd-license.php)
