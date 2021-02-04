# CHANGELOG

## [Current development]

* Overhauled preview generation
    * No longer done in `PreviewBuilder::read()`, each block now creates its own preview
    
* add phpseclib/bcmath_compat in composer.json to fix issue with missing bccomp function from bcmath module

* fix service deletion

* added function `substr` to core package

* added `ordinal` system entity

* fixed `DefaultFilterResult::isSlotEmpty()` to check empty string too (was `isset` only)

* added abilty to set predefined values for NOP filter

* fixed amazon auth code refresh process - was overwriting client data and had bad expiration handling

* update Alexa Skill manifest from platform configuration

## [Releases]

### 0.18.6 - 2021-01-08

* new dialogflow api
* removed unused dependencies from core package definition

### 0.18.5 - 2021-01-08

* Two more functions made available in expression language
* Remove faulty log statement in `IfElement` that assumed `$then_result` would always be a simple string
* Added `arrayDiffRecursive` function to `ArrayUtil`

### 0.18.4 - 2021-01-08

* Fixed method `isMediaRequest` in `AmazonCommandReqeust` - [#14](https://github.com/zef-dev/convoworks-core/issues/14)

### 0.18.3 - 2020-12-23

* Corrected `DialogflowPublisher` parent class arguments


### 0.18.2 - 2020-12-21

* Convo request is now optional in evaluateString() process - #11

### 0.18.0 - 2020-12-05

* Initial release & source import

