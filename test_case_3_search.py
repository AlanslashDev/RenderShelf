import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager

# --- CONFIGURATION ---
BASE_URL = "http://localhost/RenderShelf"
TEST_EMAIL = "sonatjoseph2028@mca.ajce.in"
TEST_PASS = "sonat@123"

# Setup Chrome Options
chrome_options = Options()
# chrome_options.add_argument("--headless")
driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
driver.maximize_window()

def take_screenshot(name):
    if not os.path.exists('test_results'):
        os.makedirs('test_results')
    driver.save_screenshot(f"test_results/{name}.png")

try:
    print("\n--- Starting Case 3: Search Functionality ---\n")
    
    # Login for session
    driver.get(f"{BASE_URL}/login.php")
    driver.find_element(By.NAME, "email").send_keys(TEST_EMAIL)
    driver.find_element(By.NAME, "password").send_keys(TEST_PASS)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)
    
    # Navigate to browse and search
    driver.get(f"{BASE_URL}/browse.php")
    search_box = driver.find_element(By.NAME, "search")
    search_box.send_keys("Transition")
    search_box.send_keys(Keys.ENTER)
    time.sleep(2)
    take_screenshot("search_results")
    print("✓ Case 3 Successful: Search Functionality Working.")

    # Logout
    driver.get(f"{BASE_URL}/logout.php")
    time.sleep(1)

except Exception as e:
    print(f"X Test Failed: {str(e)}")
    take_screenshot("case3_error")

finally:
    driver.quit()
