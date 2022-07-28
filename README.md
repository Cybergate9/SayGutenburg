# The idea

Take take Project Gutenburg texts and turn them into 'audiobooks'

[Project Gutenberg](https://www.gutenberg.org) and [Project Gutenberg Australia](https://www.gutenberg.net.au/)

# 'Say' on OSX

['Say'](https://ss64.com/osx/say.html) command line tool on OSX can speak relatively sanely, although work will be required to better implement 'pauses' at full stops and paragraph breaks. Should be doable using voice commands as referenced in Apple docs.


# Initial Test Code

I've implemented initial 'book processing' test code in PHP, currently given a url it can:

* character, line and word count

* identify, isolate and array-load book metadata

* identify start and end of book Gutenburg markers (their line numbers)

* identify, isolate and array-load book contents and chapters


# Credit where credits due

Thanks to Charlie Harrington who triggered of [this idea on his blog](https://www.charlieharrington.com/flow-and-creative-computing/)

# References

[Apple docs on voice commands](https://developer.apple.com/library/archive/documentation/UserExperience/Conceptual/SpeechSynthesisProgrammingGuide/FineTuning/FineTuning.html#//apple_ref/doc/uid/TP40004365-CH5-SW11)



