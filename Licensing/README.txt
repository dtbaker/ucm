Ultimate Client Manager - Licensing
===================================

What is in this folder
----------------------

  third-party-libraries.html   The manifest, as a web page. Open this one.
  third-party-libraries.tsv    The same manifest as tab separated values, for
                               anyone who wants to process it. This is the
                               file the release build reads, so it is the
                               authoritative copy; the web page is generated
                               from it.
  licenses/                    The full text of every licence referenced by
                               the manifest.

The manifest lists, for every third party component shipped inside
"Main Files": its name, the version bundled, its licence, the copyright
holder, the address of the upstream project, the path it lives at inside
"Main Files", and which file in licenses/ carries its terms.


How Ultimate Client Manager itself is licensed
----------------------------------------------

Your purchase from CodeCanyon grants you an Envato Market licence - either
the Regular Licence or the Extended Licence, depending on which you bought.
The terms are at https://codecanyon.net/licenses/standard and that licence
governs the application code, the stylesheets, the images and the design.

It does not govern the third party components listed in the manifest. Those
remain under their own licences, which are reproduced in licenses/. Where a
component is under the GPL or the LGPL, the terms of that licence apply to
that component, and you have the rights that licence gives you in respect of
it. Nothing in the Envato licence takes those rights away, and nothing in
this folder is an attempt to relicense anybody else's work.

In practice this means the product is split licensed: the Envato licence for
the parts written for Ultimate Client Manager, each component's own licence
for the parts that were not.


Attribution
-----------

Every component in the manifest keeps its original copyright header inside
the shipped source file. The manifest repeats the copyright holder and the
upstream address so that the credit is readable without opening the code.

If you redistribute any of these components outside this product, the
attribution requirements are those of the component's own licence, not ours.


Components with no licence text
-------------------------------

Eight of the bundled components carry no licence file and no licence header
in the copy that ships. They are marked UNRESOLVED in the manifest and the
situation of each is set out in licenses/UNRESOLVED.txt - including which
ones are simply missing a file that exists upstream, and which ones have no
known licence at all. That file is deliberately blunt about the difference.
