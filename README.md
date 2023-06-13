## venmo

Performs a bulk validation of Venmo accounts. Validates if each account in the provided list is a legitimate Venmo
account.

### Usage

```
venmo [--accountList=list.txt] [--proxy=PROXY] [--threadCount=10]
```

### Options

- `--accountList=list.txt`: Path to the file containing the list of Venmo accounts to validate. (default: list.txt)
- `--proxy=PROXY`: Proxy configuration to use. Use [random(length)] to generate a random string. Example: socks5:
  //user-s-[random(10)]:pass@host:port
- `--threadCount=10`: Number of concurrent threads to use during validation. (default: 10)

### Description

This command initiates the process to validate Venmo accounts by performing bulk validation. It takes a list of Venmo
accounts as input and checks if each account is a legitimate Venmo account.

### Examples

#### Validate Venmo accounts using the default list file:

```
venmo
```

#### Validate Venmo accounts using a custom list file:

```
venmo --accountList=custom_list.txt
```

#### Validate Venmo accounts using a proxy configuration:

```
venmo --proxy=socks5://user:pass@host:port
```

#### Validate Venmo accounts with a specific number of concurrent threads:

```
venmo --threadCount=5
```