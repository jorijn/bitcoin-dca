.. _getting-notified:

Getting notified when Bitcoin DCA makes a purchase or does a withdrawal
=======================================================================
If you want to get notifications from Bitcoin DCA when purchases and withdrawals are made you can configure the application
to do so.

.. list-table::
   :header-rows: 1

   * - Protocol
     - Notifications
   * - Email
     - Buy, Withdraw
   * - Telegram
     - Buy, Withdraw, New Bitcoin DCA versions

Sending email from Bitcoin DCA
------------------------------
Sending an email is difficult; usually, internet providers limit home connections in connecting outside to prevent
infected computers from sending out spam. For this reason, Bitcoin DCA provides multiple providers you can choose from
for sending your email through various APIs.

Generally, each provider will ask you to sign up and verify at least your email address. Some provide more control and
allow you to verify an entire domain. Low volumes of email are usually free.

Bitcoin DCA supports these email providers. Sending through HTTP is preferred because it has the least risk of getting blocked.
However, if you prefer SMTP you can choose that as well.

The configuration parameter ``NOTIFICATION_EMAIL_DSN`` accepts input on how to send the email by providing a `DSN <https://en.wikipedia.org/wiki/Data_source_name>`_.

.. list-table::
   :header-rows: 1

   * - Provider
     - DSN for sending through SMTP
     - DSN for sending through HTTP
   * - Amazon SES
     - ses+smtp://USERNAME:PASSWORD@default
     - ses+https://ACCESS_KEY:SECRET_KEY@default
   * - Google Gmail
     - gmail+smtp://USERNAME:PASSWORD@default
     -
   * - Mailchimp Mandrill
     - mandrill+smtp://USERNAME:PASSWORD@default
     - mandrill+https://KEY@default
   * - Mailgun
     - mailgun+smtp://USERNAME:PASSWORD@default
     - mailgun+https://KEY:DOMAIN@default
   * - Mailjet
     - mailjet+smtp://ACCESS_KEY:SECRET_KEY@default
     -
   * - Postmark
     - postmark+smtp://ID@default
     -
   * - Sendgrid
     - sendgrid+smtp://KEY@default
     - sendgrid+api://KEY@default
   * - Sendinblue
     - sendinblue+smtp://USERNAME:PASSWORD@default
     -
   * - `Your own SMTP server`
     - smtp://USERNAME:PASSWORD@HOSTNAME:PORT
     -

.. note::
   The easiest service to use is probably Gmail as most people already own a gmail address. If you use 2FA you need to create an `app password <https://support.google.com/mail/answer/185833?hl=en-GB>`_ first.

Under the hood, Bitcoin DCA uses the Symfony Mailer. You can consult `their documentation <https://symfony.com/doc/current/mailer.html#using-built-in-transports>`_
for more advanced topics such as alternative ports, high availability and load balancing.

For example, when using Gmail your configuration would look like this:

.. code-block:: bash
   :caption: Example for sending email from Gmail

   NOTIFICATION_EMAIL_ENABLED=1
   NOTIFICATION_EMAIL_TO=satsstacker@gmail.com
   NOTIFICATION_EMAIL_DSN=gmail+smtp://USERNAME:PASSWORD@default

Using Telegram with Bitcoin DCA
-------------------------------
If you want to get notifications on Telegram from Bitcoin DCA you can follow these instructions:

1. Open a new chat with `Botfather <https://t.me/botfather>`_;
2. Tell it you want to create a new bot with ``/newbot``;
3. Pick a name. Be creative;
4. Pick a username for your new bot, it should end in ``bot`` and should be unique across the Telegram network. For example: `BobsTelegramDCABot`;
5. Note the secret token you just received. It looks like ``12345:ABCDetc``;
6. Talk to your new bot by clicking the ``t.me/BobsTelegramDCABot`` link you received. This will tell Telegram the bot is allowed to talk to you;
7. Open a new chat with `GetMyID bot <https://t.me/getmyid_bot>`_;
8. Note the ID you received, this is your unique Telegram ID.

Now, configure Bitcoin DCA with this information:

.. code-block:: bash
   :caption: Example for connecting Bitcoin DCA to Telegram

   NOTIFICATION_TELEGRAM_ENABLED=1
   NOTIFICATION_TELEGRAM_DSN=telegram://BOTFATHERSECRET@default?channel=YOURTELEGRAMID
   # example: NOTIFICATION_TELEGRAM_DSN=telegram://12345:ABCDetc@default?channel=123456
