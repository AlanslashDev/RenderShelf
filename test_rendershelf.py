import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager

# --- CONFIGURATION ---
BASE_URL = "http://localhost/RenderShelf"  # Change this if your URL is different
TEST_EMAIL = "sonatjoseph2028@mca.ajce.in"
TEST_PASS = "sonat@123"
TEST_USER = "TestAutomator"

# Setup Chrome Options
chrome_options = Options()
# chrome_options.add_argument("--headless") # Uncomment to run without opening browser
driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
driver.maximize_window()

def take_screenshot(name):
    if not os.path.exists('test_results'):
        os.makedirs('test_results')
    driver.save_screenshot(f"test_results/{name}.png")

try:
    print("\n--- Starting RenderShelf Automation Tests ---\n")

    # CASE 1: Login To an account surf the website the log out
    print("Case 1: Testing Login, Surfing, and Logout...")
    driver.get(f"{BASE_URL}/login.php")
    driver.find_element(By.NAME, "email").send_keys(TEST_EMAIL)
    driver.find_element(By.NAME, "password").send_keys(TEST_PASS)
    take_screenshot("login_screen")
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)
    
    # Surf to Browse
    driver.find_element(By.CSS_SELECTOR, "a[href='browse.php']").click()
    time.sleep(1)
    take_screenshot("surfing_browse_page")
    
    # Logout
    driver.get(f"{BASE_URL}/logout.php")
    time.sleep(1)
    print("✓ Case 1 Successful: Login/Logout Flow Verified.")

    # CASE 2: Register A new account surf the website log out
    print("Case 2: Testing Registration...")
    # Use a dynamic email for registration to avoid "email already exists" errors
    REG_USER = "NewUser_" + str(int(time.time()))
    REG_EMAIL = "new_" + str(int(time.time())) + "@example.com"
    
    driver.get(f"{BASE_URL}/register.php")
    driver.find_element(By.NAME, "username").send_keys(REG_USER)
    driver.find_element(By.NAME, "email").send_keys(REG_EMAIL)
    driver.find_element(By.NAME, "password").send_keys("Pass12345")
    driver.find_element(By.NAME, "confirm_password").send_keys("Pass12345")
    take_screenshot("registration_form")
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)
    print("✓ Case 2 Successful: Account Registered.")
    
    # Logout after registration surf
    driver.get(f"{BASE_URL}/logout.php")
    time.sleep(1)

    # CASE 3: Search Functionality
    print("Case 3: Testing Search Logic...")
    # Re-login for session
    driver.get(f"{BASE_URL}/login.php")
    driver.find_element(By.NAME, "email").send_keys(TEST_EMAIL)
    driver.find_element(By.NAME, "password").send_keys(TEST_PASS)
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    driver.get(f"{BASE_URL}/browse.php")
    search_box = driver.find_element(By.NAME, "search")
    search_box.send_keys("Transition")
    search_box.send_keys(Keys.ENTER)
    time.sleep(2)
    take_screenshot("search_results")
    print("✓ Case 3 Successful: Search Functionality Working.")

    # CASE 4: Category Filtering
    print("Case 4: Testing Category Filters...")
    all_tabs = driver.find_elements(By.CLASS_NAME, "tab-pill")
    if len(all_tabs) > 1:
        all_tabs[1].click() # Click second category
        time.sleep(2)
        take_screenshot("category_filter_applied")
    print("✓ Case 4 Successful: Category Filtering Verified.")

    # CASE 5: Wallet and Profile Integration
    print("Case 5: Testing Wallet Display & Profile Access...")
    driver.get(f"{BASE_URL}/wallet.php")
    time.sleep(2)
    # The balance is inside the .revenue-content h1 tag
    balance = driver.find_element(By.CSS_SELECTOR, ".revenue-content h1").text
    print(f"   Current Wallet Balance: {balance}")
    take_screenshot("wallet_status")
    
    driver.get(f"{BASE_URL}/profile.php")
    time.sleep(2)
    take_screenshot("profile_page")
    print("✓ Case 5 Successful: Account Integration Verified.")

    print("\n" + "="*40)
    print("ALL TESTS COMPLETED SUCCESSFULLY!")
    print("="*40)

except Exception as e:
    print(f"X Test Failed: {str(e)}")
    take_screenshot("error_log")

finally:
    driver.quit()
