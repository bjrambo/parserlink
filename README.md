# parserlink
parserlink


# Coding standard with C# coding standards by [@popekim](https://github.com/popekim) [google documents](https://docs.google.com/document/d/1ymFFTVpR4lFEkUgYNJPRJda_eLKXMB6Ok4gpNWOo4rc/edit?pageId=100917518571510124826#)
When naming a function, it is explicitly set. But, If it is display functions, simply allow a name for the page break.

* dispParserlinkAdminConfigDisplay // O.K
* procParserlinkAdminInsertConfig // O.K
* procParserlinkAdminConfig // WRONG
* dispParserlinkAdminConfig // O.K : Simply name for the page break.


Always place an opening curly brace ({) in a new line

Add curly braces even if there's only one line in the scope
```php
if ($moduleModel)
{
	return;
}
```

Always have a default case for a switch statement.
```php
switch($modulename)
{
	case 'module':
		...
		break;
	default:
		break;
}
```

Always last array value next insert comma(,).
