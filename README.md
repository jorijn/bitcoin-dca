# Bitcoin DCA using the BL3P Exchange

## Requirements
* You need to have an account on BL3P: https://bl3p.eu/.
* You need to have Docker installed: https://docs.docker.com/get-docker/
* You need to have an API key active on BL3P. Create one here: https://bl3p.eu/security. It needs **read**, **trade** and **withdraw** permission. For safety, I would recommend locking the API key to the IP address your planning on running this tool from.

## About this software
The DCA tool is built with flexibility in mind, allowing you to specify your own schedule of buying and withdrawing. A few examples that are possible:

* Buy each week, never withdraw.
* Buy monthly and withdraw at the same time to reduce exchange risk.
* Buy each week but withdraw only at the end of the month to save on withdrawal fees.

Only withdrawing to a regular Bitcoin address is supported currently. I do have plans for the future to configure a xpub key and derive new addresses for each withdrawal executed.

## Installation & Upgrading

### Using Docker Hub (easiest)

### Installing
Use these commands to download this tool from Docker Hub.

```bash
$ docker pull jorijn/bl3p-dca:latest
```

### Upgrading
Using these commands you can download the newest version from Docker Hub.

```bash
$ docker image rm jorijn/bl3p-dca
$ docker pull jorijn/bl3p-dca:latest
```

### Build your own (more control)
If you desire more control, pull this project from GitHub and build it yourself. To do this, follow these commands:

###
```bash
cd ~
git clone https://github.com/Jorijn/bl3p-dca.git
cd bl3p-dca
docker build . -t jorijn/bl3p-dca:latest
```

When an upgrade is available, run `git pull` to fetch the latest changes and rebuild the docker container. 

## Configuration
Create a new file somewhere that will contain the configuration needed for the tool to operate. If your account is called `bob` and your home directory is `/home/bob` lets create a new file in `/home/bob/.bl3p-dca`:

```
BL3P_PRIVATE_KEY=bl3p private key here
BL3P_PUBLIC_KEY=bl3p public key here
BL3P_API_URL=https://api.bl3p.eu/1/
BL3P_WITHDRAW_ADDRESS=hardware wallet address here
```

You can test that it work properly with:

```bash
$ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest balance
```

If successful, you should see a table containing your balances on the exchange:

```$xslt
+----------+----------------+----------------+
| Currency | Balance        | Available      |
+----------+----------------+----------------+
| BTC      | 0.00000000 BTC | 0.00000000 BTC |
| EUR      | 10.0000000 EUR | 10.0000000 EUR |
| BCH      | 0.00000000 BCH | 0.00000000 BCH |
| LTC      | 0.00000000 LTC | 0.00000000 LTC |
+----------+----------------+----------------+
```

### Testing
For safety, I recommend buying and withdrawing at least once manually to verify everything works before proceeding with automation.

#### Buying €10,00 of Bitcoin
```bash
$ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest buy 10
```

#### Withdrawing to your hardware wallet
```bash
$ docker run --rm -it --env-file=/home/bob/.bl3p-dca jorijn/bl3p-dca:latest withdraw --all
```

**It will ask you:** 
Ready to withdraw 0.00412087 BTC to Bitcoin Address bc1abcdefghijklmopqrstuvwxuz123456? A fee of 0.0003 will be taken as withdraw fee [y/N]:

**When testing, make sure to verify the displayed Bitcoin address matches the one configured in your `.bl3p-dca` configuration file. When confirming this question, withdrawal executes immediately.**

## Automating buying and withdrawing
TODO
