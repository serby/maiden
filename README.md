# Maiden - A PHP Build Tool

A very simple build system for writing build and other common development lifecycle targets.

## Why another build tool? What is wrong with make, rake, phing, ant etc.

At Clock we live and breath PHP, we love it! Every technically member of staff has a solid understanding and is setup to code PHP.
All the web sites/application, all the tools, as much as possible we code in PHP.

Previously we have used PHING to build, install, etc projects, but when your targets get more advanced, writing and debugging
becomes really hard and really time consuming. It didn't make any sense! Everyone has all the tools to write and debug PHP and yet we were
programming in XML. Also our QA tools couldn't be used to ensure that our PHING/ANT build targets where valid and of a descent quality.

Maiden aims to keep all of your project code in a common language. If you have PHP tools/classes that your project uses then your build targets
can also take advantage of these. 

Another advantage of Maiden is that your build files benefit from all the power of PHPs abstraction, inheritance, namespaces, std library etc, 
so you write less code and have more powerful maintainable build targets.

## Installation

     sudo cd /usr/share/php
     git clone git://github.com/PabloSerbo/maiden.git
     cd maiden
     ./maiden install

## Usage

     maided -h 						# Show help
     maiden -l 						# List all targets in Maid.php
     maiden <target>			# Run <target>

## Credits
[Paul Serby](https://github.com/PabloSerbo/)

## Licence
Licenced under the [New BSD License](http://opensource.org/licenses/bsd-license.php)
