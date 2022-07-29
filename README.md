# The idea

Take Project Gutenburg texts and turn them into 'audiobooks'

[Project Gutenberg](https://www.gutenberg.org) and [Project Gutenberg Australia](https://www.gutenberg.net.au/)

So two steps are required:

* process books into their component parts (which will probably be quite a challenge because they're have a variety of layout logics)

* once we have books in 'chapters' we can pass them to some sort of reading (Text-To-Speech, TTS) software


# 'Say' on OSX

['Say'](https://ss64.com/osx/say.html) command line tool on OSX can speak relatively sanely, although work will be required to better implement 'pauses' at full stops and paragraph breaks. Should be doable using voice commands as referenced in Apple docs.

# TTS by Mozilla

Will probably need to [look into this](https://github.com/mozilla/TTS), Mozzila Text-to-Speech (TTS), even if we only used already created models.


# Initial Test Code

I've implemented initial 'book processing' test code in PHP, currently given a url it can:

* character, line and word count

* identify, isolate and array-load book metadata

* identify start and end of book Gutenburg markers (and their line numbers)

* identify, isolate and array-load book 'contents block' and chapters list from within, into 'Contents Block array'

* (removed temporarily) if no 'contents block', identify 'Chapter X's within text and their meta, store in 'Chapters array'

* for each Chapter find its starting line number 'markers' from within text and record them into 'Chapters array'

* based on all this, the test processor cuts up the input and outputs each chapter as a separate text file


Testing has *only* taken place on the single Agatha Christie book in the test code so far (https://www.gutenberg.org/files/863/863-0.txt)

* with the Agatha Christie, under current logic, the Contents Block outputs as Chapter 1..


# Credit where credits due

Thanks to Charlie Harrington who triggered off [this idea on his blog](https://www.charlieharrington.com/flow-and-creative-computing/)

# References

* [Apple docs on voice commands](https://developer.apple.com/library/archive/documentation/UserExperience/Conceptual/SpeechSynthesisProgrammingGuide/FineTuning/FineTuning.html#//apple_ref/doc/uid/TP40004365-CH5-SW11)

* [Project Gutenberg](https://www.gutenberg.org)

* [Project Gutenberg Australia](https://www.gutenberg.net.au/)

* [LibreVox](https://librivox.org/) - already done! Human voiced audiobooks



