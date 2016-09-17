# invcr

Invoices in your Linux terminal.
## Profiles
### src/profiles/codemash.php
```
<?php

return [
	'company_name' => 'Codemash',
	'company_contact' => 'Matthias Van Parijs',
	'company_street' => '',
	'company_zip_city' => '',
	'company_telephone' => '',
	'company_website' => 'http://www.codemash.be',
	'company_email' => 'matthias@codemash.be',
	'company_vat' => '',
	'company_bank_nr_swift' => '',
	'company_bank_nr_iban' => '',
	'company_bank' => '',
];
```
### src/profiles/client.php
```
<?php

return [
	'client_name' => '',
	'client_on_invoice_name' => '',
	'client_contact' => '',
	'client_address' => '',
	'client_vat' => '',
];
```

## Settings
### src/settings.php
```
<?php

// How to authorize Google Drive for uploads:
// http://stackoverflow.com/questions/19766912/how-do-i-authorise-an-app-web-or-installed-without-user-intervention-canonic

return [
	'vat' => 21,

	'google_refresh_token' => '',
	'google_upload_folder' => '',
];
```

### src/google_credentials.json

Add the Google Credentials file from the Google API console.