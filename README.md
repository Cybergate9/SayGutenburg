

# The idea

Take Project Gutenburg texts and turn them into 'audiobooks'

[Project Gutenberg](https://www.gutenberg.org) and [Project Gutenberg Australia](https://www.gutenberg.net.au/)

So two steps are required:

1. process books into their component parts (which will probably be quite a challenge because they're in a variety of layout logics)

2. once we have books in 'chapters' we can pass them to some sort of reading (Text-To-Speech, TTS) software

We are currently at step 1.


# Usage:

php testx.php [-t] [-t1] [-t3] [-p] [-d] [-D]

where:

* -t test only (don't output chapter files), default off

* -t1 run code verbose to test1 point 1 and exit, default off

* -t2 run code verbose to test point 2 and exit, default off

* -p invode preprocessor (over entire text), default off

* -d create dump file (as dir/bookname-debug-coded.txt), default off

* -D create hex'd dumpfile (as dir/bookname-debug-coded.txt), default off


# Progress (17Aug2022, test5.php)

More refined, all output goes into 'book title' subdir, more error checking and exit points, dealing with roman numerals

Testing on:

* Agatha Christie's "The Mysterious Affair at Styles" 

* Persuasion by Jane Austin

* A Tale of Two Cities by Charles Dickens 

* Moby Dick by Herman Melville

* (this one is a good 'guaranteed to break' test) - [The peoples of Europe](https://www.gutenberg.org/cache/epub/68562/pg68562.txt)



# Progress (6Aug2022, test3.php)

Is more sane, predictable, and therefore robust. Tests fairly reliably on two 'types', over three books.

A frustrating case is [this book](https://www.gutenberg.org/cache/epub/68562/pg68562.txt) which while a human can work out what's going on it's a large pain in the backside to line process because it does odd things like use _ to lead and trail chapter titles, breaks chapter titles over lines in both content block and within text etc. I'm guessing this one may fall into the bucket of hand edit first then process.

That raises the question of sensing and providing some warning/guidance from the processor when layouts are not understood - an interesting future to do maybe.


# Initial Test Code (27Jul2022, test.php)

I've implemented initial 'book processing' test code in PHP, currently given a url it can:

* character, line and word count

* identify, isolate and array-load book metadata

* identify start and end of book Gutenburg markers (and their line numbers)

* identify, isolate and array-load book 'contents block' and chapters list from within, into 'Contents Block array'

* (removed temporarily) if no 'contents block', identify 'Chapter X's within text and their meta, store in 'Chapters array'

* for each Chapter find its starting line number 'markers' from within text and record them into 'Chapters array'

* based on all this, the test processor cuts up the input and outputs each chapter as a separate text file


Testing has *only* taken place on the single Agatha Christie book in the test code (test.php) so far (https://www.gutenberg.org/files/863/863-0.txt)

* with the Agatha Christie, under current logic, the Contents Block outputs as Chapter 1..


# Credit where credits due

Thanks to Charlie Harrington who triggered off [this idea on his blog](https://www.charlieharrington.com/flow-and-creative-computing/)


# 'Say' on OSX

['Say'](https://ss64.com/osx/say.html) command line tool on OSX can speak relatively sanely, although work will be required to better implement 'pauses' at full stops and paragraph breaks. Should be doable using voice commands as referenced in Apple docs. But it's good enough in test already to say if step one can be solved, then step two is just a choice of tools.


# References

* [Apple docs on voice commands](https://developer.apple.com/library/archive/documentation/UserExperience/Conceptual/SpeechSynthesisProgrammingGuide/FineTuning/FineTuning.html#//apple_ref/doc/uid/TP40004365-CH5-SW11)

* [Project Gutenberg](https://www.gutenberg.org)

* [Project Gutenberg Australia](https://www.gutenberg.net.au/)

* [LibreVox](https://librivox.org/) - already done! Human voiced audiobooks

* [Mozilla Text-to-Speech (TTS)](https://github.com/mozilla/TTS)

* [Coqui TTS](https://github.com/coqui-ai/TTS)

* [Hacker News thread on TTS's](https://news.ycombinator.com/item?id=32380045)

# Prior Art

Other attempts to extract chapters from GUtenberg Books

* [chapterize](https://github.com/JonathanReeve/chapterize)

* [text splitter](https://github.com/jdmartin/gutenberg-text-splitter)



