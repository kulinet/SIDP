"""
Bot rekap NON TENDER LPSE (SPSE) -> Excel, versi SELENIUM (Chrome otomatis).

Contoh:
  python lpse_nontender_bot_selenium.py --limit 10
  python lpse_nontender_bot_selenium.py --headless
"""
import lpse_bot
from lpse_bot_selenium import SeleniumLpseBot
from lpse_nontender_bot import NonTenderConfig


class SeleniumNonTenderBot(NonTenderConfig, SeleniumLpseBot):
    pass


if __name__ == "__main__":
    lpse_bot.main(SeleniumNonTenderBot,
                  lambda ap: ap.add_argument("--headless", action="store_true",
                                             help="jalankan Chrome tanpa jendela"))
