import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
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
    print("\n--- Starting Case 5: Wallet and Profile Integration ---\n")
    
    # Login for session
    driver.get(f"{BASE_URL}/login.php")
    driver.find_element(By.NAME, "email").send_keys(TEST_EMAIL)
    driver.find_element(By.NAME, "password").send_keys(TEST_PASS)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)
    
    # Wallet check
    driver.get(f"{BASE_URL}/wallet.php")
    time.sleep(2)
    # The balance is inside the .revenue-content h1 tag
    balance = driver.find_element(By.CSS_SELECTOR, ".revenue-content h1").text
    print(f"   Current Wallet Balance: {balance}")
    take_screenshot("wallet_status")
    
    # Profile check
    driver.get(f"{BASE_URL}/profile.php")
    time.sleep(2)
    take_screenshot("profile_page")
    print("✓ Case 5 Successful: Account Integration Verified.")

    # Logout
    driver.get(f"{BASE_URL}/logout.php")
    time.sleep(1)

except Exception as e:
    print(f"X Test Failed: {str(e)}")
    take_screenshot("case5_error")

finally:
    driver.quit()
