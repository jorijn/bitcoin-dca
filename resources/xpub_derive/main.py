import json

import fire

from btclib import bip32, slip32


def derive(mpub: str, start: int, length: int):
    """
    Will generate list of derived addresses, starting at index <n> for <length>.
    :param mpub: master extended public key
    :param start: starting index to generate address list
    :param length: how many addresses to generate
    :return: a json list of derived addresses
    """

    address_list = []
    for index in range(start, start + length):
        xpub = bip32.derive(xkey=mpub, path=f"./0/{index}")
        address = slip32.address_from_xpub(xpub).decode("ascii")
        address_list.append(address)

    return json.dumps(address_list)


if __name__ == "__main__":
    fire.Fire({
        "derive": derive
    })
