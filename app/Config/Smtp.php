<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */
namespace Config;

use CodeIgniter\Config\BaseConfig;

class Smtp extends BaseConfig
{

	/**
	 * @var string
	 */
	public $fromEmail;

	/**
	 * @var string
	 */
	public $fromName;

	/**
	 * @var string
	 */
	public $recipients;

	/**
	 * The "user agent"
	 *
	 * @var string
	 */
	public $userAgent = 'CodeIgniter';

	/**
	 * The mail sending protocol: mail, sendmail, smtp
	 *
	 * @var string
	 */
	public $protocol = 'smtp';

	/**
	 * The server path to Sendmail.
	 *
	 * @var string
	 */
	public $mailPath = '/usr/sbin/sendmail';

	/**
	 * SMTP Server Address
	 *
	 * @var string
	 */
	public $SMTPHost = 'smtp.googlemail.com';

	/**
	 * SMTP Username
	 *
	 * @var string
	 */
	// Loaded from .env (smtp.SMTPUser). Never hardcode credentials here.
	public $SMTPUser = '';


	/**
	 * SMTP Password
	 *
	 * @var string
	 */
	// Loaded from .env (smtp.SMTPPass). Never hardcode credentials here.
	public $SMTPPass = '';

	/**
	 * SMTP Port
	 *
	 * @var integer
	 */
	public $SMTPPort = 465;

	/**
	 * SMTP Timeout (in seconds)
	 *
	 * @var integer
	 */
	public $SMTPTimeout = 60;

	/**
	 * Enable persistent SMTP connections
	 *
	 * @var boolean
	 */
	public $SMTPKeepAlive = false;

	/**
	 * SMTP Encryption. Either tls or ssl
	 *
	 * @var string
	 */
	public $SMTPCrypto = 'ssl';

	/**
	 * Enable word-wrap
	 *
	 * @var boolean
	 */
	public $wordWrap = true;

	/**
	 * Character count to wrap at
	 *
	 * @var integer
	 */
	public $wrapChars = 76;

	/**
	 * Type of mail, either 'text' or 'html'
	 *
	 * @var string
	 */
	public $mailType = 'html';

	/**
	 * Character set (utf-8, iso-8859-1, etc.)
	 *
	 * @var string
	 */
	public $charset = 'UTF-8';

	/**
	 * Whether to validate the email address
	 *
	 * @var boolean
	 */
	public $validate = false;

	/**
	 * Email Priority. 1 = highest. 5 = lowest. 3 = normal
	 *
	 * @var integer
	 */
	public $priority = 3;

	/**
	 * Newline character. (Use "\r\n" to comply with RFC 822)
	 *
	 * @var string
	 */
	public $CRLF = "\r\n";

	/**
	 * Newline character. (Use "\r\n" to comply with RFC 822)
	 *
	 * @var string
	 */
	public $newline = "\r\n";

	/**
	 * Enable BCC Batch Mode.
	 *
	 * @var boolean
	 */
	public $BCCBatchMode = false;

	/**
	 * Number of emails in each BCC batch
	 *
	 * @var integer
	 */
	public $BCCBatchSize = 200;

	/**
	 * Enable notify message from server
	 *
	 * @var boolean
	 */
	public $DSN = false;

	/**
	 * Pull SMTP credentials/host from the environment at construction so no
	 * secret ships in tracked source. Set these in `.env`:
	 *   smtp.SMTPHost, smtp.SMTPUser, smtp.SMTPPass, smtp.SMTPPort,
	 *   smtp.SMTPCrypto, smtp.protocol, smtp.fromEmail, smtp.fromName
	 * (CI4's BaseConfig also auto-applies these, but we set them explicitly to
	 * keep the mapping obvious and independent of env-prefix casing.)
	 */
	public function __construct()
	{
		parent::__construct();
		$this->protocol   = env('smtp.protocol', $this->protocol);
		$this->SMTPHost   = env('smtp.SMTPHost', $this->SMTPHost);
		$this->SMTPUser   = env('smtp.SMTPUser', $this->SMTPUser);
		$this->SMTPPass   = env('smtp.SMTPPass', $this->SMTPPass);
		$this->SMTPPort   = (int) env('smtp.SMTPPort', $this->SMTPPort);
		$this->SMTPCrypto = env('smtp.SMTPCrypto', $this->SMTPCrypto);
		$this->fromEmail  = env('smtp.fromEmail', $this->fromEmail);
		$this->fromName   = env('smtp.fromName', $this->fromName);
	}
}
