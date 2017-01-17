import scrapy
import logging
import time
from datetime import datetime
from ali_express.items import DressItem

SLEEP_TIME = 1500


class AliExpressSpider(scrapy.Spider):
    name = 'aliexpress'
    logger = None
    max_price = 0
    from_price = 0
    to_price = 0.1
    increment_to_price = 0.1
    items_limit = 8000
    sleep_time = SLEEP_TIME
    log_file = 'log'
    start_url = ''
    desc_price = '&SortType=price_desc'
    asc_price = '&SortType=price_asc'
    handle_httpstatus_list = [301, 302]

    def __init__(self, **kwargs):
        super(AliExpressSpider, self).__init__(**kwargs)
        self.logger = self.initialize_logger()

    def start_requests(self):
        # parse max price
        yield scrapy.Request(self.start_url + self.desc_price, self.parse_max_price, dont_filter=True)
        # parse from and to prices
        yield scrapy.Request(self.start_url + self.asc_price, self.parse_from_to_prices, dont_filter=True)
        # parse all products
        while self.to_price <= self.max_price:
            # parse products in a given price range
            yield scrapy.Request(self.start_url + self.get_from_to_price(), self.parse, dont_filter=True)
            self.increment_prices()

    def parse(self, response):
        if self.is_anti_spider_redirect(response):
            self.sleep_spider()
            yield scrapy.Request(response.url, self.parse, dont_filter=True)

        self.validate_dress_count(response)
        for dress_url in response.css('.product::attr(href)').extract():
            # remove unnecessary symbols from url
            dress_url = dress_url.strip('//')
            dress_url = dress_url[0:dress_url.index('?')+1]
            last_updated = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            dress_item = DressItem(url=dress_url, last_updated=last_updated)
            yield dress_item

        next_page = response.css('.ui-pagination-next::attr(href)').extract_first()
        if next_page is not None:
            yield scrapy.Request(response.urljoin(next_page), callback=self.parse, dont_filter=True)

    def parse_max_price(self, response):
        if self.is_anti_spider_redirect(response):
            self.sleep_spider())
            yield scrapy.Request(response.url, self.parse_max_price, dont_filter=True)
        else:
            self.max_price = self.format_price(response)
            self.logger.info('Max price: {}'.format(self.max_price))

    def parse_from_to_prices(self, response):
        if self.is_anti_spider_redirect(response):
            self.sleep_spider()()
            yield scrapy.Request(response.url, self.parse_from_to_prices, dont_filter=True)
        else:
            self.from_price = self.format_price(response)
            self.to_price = self.from_price + self.increment_to_price
            self.logger.info('From price: {}'.format(self.from_price))
            self.logger.info('To price: {}'.format(self.to_price))

    def get_from_to_price(self):
        self.logger.info('Scraping products from {}$ to {}$'.format(self.from_price, self.to_price))

        return '&minPrice={}&maxPrice={}'.format(self.from_price, self.to_price)

    @staticmethod
    def format_price(response):
        price = response.css('.value::text').extract_first()
        price = price.replace('US $', '')
        price = price.replace(',', '')
        price = price.replace('.', '')
        price = int(price) / 100

        return price

    def increment_prices(self):
        self.from_price = self.to_price + 0.01
        self.to_price += self.increment_to_price

    @staticmethod
    def is_anti_spider_redirect(response):
        anti_spider = 'anti_Spider-htmlrewrite-checklogin'
        try:
            if anti_spider in response.headers['Location']:
                return True
        except KeyError:
            return False

    def sleep_spider(self):
        self.logger.info('Spider detected! Sleeping for {} minutes'.format(self.sleep_time / 60))
        time.sleep(self.sleep_time)
        self.reduce_sleep_time()

    def reduce_sleep_time(self):
        if self.sleep_time == 60:
            self.sleep_time = SLEEP_TIME
        else:
            self.sleep_time -= 60

    def validate_dress_count(self, response):
        dresses_count = response.css('.search-count::text').extract_first()
        dresses_count = dresses_count.replace(',', '')
        dresses_count = float(dresses_count)
        dresses_count = int(dresses_count)
        self.logger.info('Dresses count: {}'.format(dresses_count))

        if dresses_count > self.items_limit:
            self.logger.info('ERROR! Dresses count: {} > {}'.format(dresses_count, self.items_limit))

    def initialize_logger(self):
        logger = logging.getLogger(__name__)
        logger.setLevel(logging.INFO)
        # create a file handler
        handler = logging.FileHandler(self.log_file)
        handler.setLevel(logging.INFO)
        # create a logging format
        formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
        handler.setFormatter(formatter)
        # add handler to the logger
        logger.addHandler(handler)

        return logger
