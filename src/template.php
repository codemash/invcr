<?php echo '<style>' . file_get_contents( 'template.css') . '</style>'; ?>

<html>
  <body>
    <table>
      <tr>
        <td class="w50">
          {{ company_name }}
        </td>
        <td class="c w50">
          <table class="lr">
            <tr>
              <td>Factuur</td>
              <td>{{ num }}</td>
            </tr>
            <tr>
              <td>Datum</td>
              <td>{{ date }}</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>Organisatie</td>
              <td>{{ client_name }}</td>
            </tr>
            <tr>
              <td>Ter attentie van</td>
              <td>{{ client_contact }}</td>
            </tr>
            <tr>
              <td>Adres</td>
              <td>{{ client_address }}</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>BTW</td>
              <td>{{ client_vat }}</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <table>
      <tr>
        <td class="w50">
          {{ company_name }} contact: {{ company_contact }}
        </td>
        <td class="w50">
          &nbsp;
        </td>
      </tr>
    </table>

    <table>
      <tr>
        <td class="c w100">
          <div class="products">
            <table class="lr">
              <tr>
                <td><strong>Omschrijving</strong></td>
                <td><strong>Bedrag</strong></td>
              </tr>
              <?php foreach ($rows as $r) { ?>
              <tr>
                <td><?php echo $r['desc']; ?></td>
                <td>€ <?php echo $r['price']; ?></td>
              </tr>
              <?php } ?>
            </table>
          </div>
        </td>
      </tr>
    </table>

    <table>
      <tr>
        <td class="w70">&nbsp;</td>
        <td class="c w30">
          <div class="total">
            <table class="lr">
              <tr>
                <td>Subtotaal</td>
                <td>€ {{ sub_total_price }}</td>
              </tr>
              <tr>
                <td>BTW</td>
                <td>{{ vat }}%</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>€ {{ vat_price }}</td>
              </tr>
              <tr>
                <td><strong>Totaal</strong>
                </td>
                <td>€ {{ total_price }}</td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>

    <div class="footer">
      <table>
        <tr>
          <td class="w100">
            Wij verzoeken je vriendelijk het bovenstaande bedrag te betalen binnen de eerstvolgende 30 dagen ({{ pay_date }}).
          </td>
        </tr>
      </table>

      <table>
        <tr>
          <td class="c w50 va-t">
            <table class="lr">
              <tr>
                <td>{{ company_name }}</td>
                <td>T {{ company_telephone }}</td>
              </tr>
              <tr>
                <td>{{ company_street }}</td>
                <td>{{ company_website }}</td>
              </tr>
              <tr>
                <td>{{ company_zip_city }}</td>
                <td>{{ company_email }}</td>
              </tr>
            </table>
          </td>
          <td class="c w50">
            <table class="lr">
              <tr>
                <td>BTW</td>
                <td>{{ company_vat }}</td>
              </tr>
              <tr>
                <td>{{ company_bank }}</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>Swift/BIC</td>
                <td>{{ company_bank_nr_swift }}</td>
              </tr>
              <tr>
                <td>IBAN</td>
                <td>{{ company_bank_nr_iban }}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
  </body>
</html>