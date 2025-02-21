import requests
from bs4 import BeautifulSoup


cnn_url = "https://www.cnn.com/"

def scrape_with_beautiful_soup(url):
    response = requests.get(url)
    soup = BeautifulSoup(response.text, 'html.parser')

    #Extract and print headlines
    headlines = soup.select('span')
    for headline in headlines:
        print(headline.text)

#Scrape headlines using Beautiful Soup

scrape_with_beautiful_soup(cnn_url)
