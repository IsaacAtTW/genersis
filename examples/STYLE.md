<!-- 基本規則 ========================================================================================================-->
***
## 基本規則 ##

##### php #####
- 需遵守 **`PSR-2`** 規則  
- 每行字數不可超過 **`120 個字元`**  
- 縮排使用 **`4 個空白`**，**`禁止用 tab`**  
- 檔案結尾**`不加 "?">`**，**`留一行空行`**  
- 一元運算子與運算元之間 **`不留空白`**  
- 二元運算子、三元運算子與運算元之間 **`至少留一個空格或換行符號`**  
- 除了設定檔和 view，每個檔案都必須宣告 **`namespace`**  
- **`namespace`** 根目錄之前 **`一律都加上 "\"`**  
- 使用 **`use`** 的方式引入其他 namespace 的程式，取代在程式裡寫 namespace 完整路徑  
- 一個定義檔只能定義 **`一個`** class / trait / interface  
- 一個 function / method 只實作 **`一個`** 功能  

##### html #####
- 縮排使用 **`tab`**  
- js code **`放在 html 之後`**  

<!-- 命名規則 ========================================================================================================-->
***
## 命名規則 ##

##### 資料夾 #####
- *系統資料夾:*  
**`全小寫`**，兩個字以上 **`用 "_" 串接`**，分類 **`用 "-" 接在最後`**  
- *namespace 資料夾:*  
**`大駝峰`**  

##### 檔案 #####

> php 檔案: 副檔名一律為 **`.php`**，不需加上其他副檔名

- *系統檔案:*  
**`全小寫`**，兩個字以上 **`用 "_" 串接`**，分類 **`用 "-" 接在最後`**  
- *定義檔:*  
**`大駝峰`**，需與 class / trait / interface **`名稱一致`**  
- *view 檔案:*  
**`小駝峰`**，需與 uri **`最後一階一致`**  

> 其他類型檔案:

- *其他檔案:*  
**`全小寫`**，兩個字以上 **`用 "_" 串接`**，分類 **`用 "-" 接在最後`**，ex: data_source-prod.json、data_source-uat.json  

##### 變數 #####
- *常數:*  
**`全大寫`**，兩個字以上 **`用 "_" 串接`**  
- *array key:*  
**`全小寫`**，兩個字以上 **`用 "_" 串接`**  
- *變數:*  
**`小駝峰`**，**`不可用 "_" 開頭`**，須符合以下特殊規則:  
	- *bool:* prefix **`is-`**  
	- *anonymous function:* prefix **`func-`**  
	- *物件:* 只能是 **`class name`** 或是 suffix **`-Obj`**  
	- *array:*
		- *一維且自然排序:* suffix **`-List`**  
			***註：用 implode() 結果有意義的情境***  
		- *一維且非自然排序:* suffix **`-Data`**  
		- *多維:* suffix **`-Arr`**  
	- *用逗號 implode 出來的字串:* suffix **`-ListStr`**  
	- *timestamp:* suffix **`-Ts`**  
	- *datetime:* suffix **`-Dt`**  
	- *其他 date 字串:* suffix **`-Date`**  

##### 函式 #####
- *函式:*  
**`小駝峰`**，**`不可用 "_" 開頭`**，須符合以下特殊規則:  
	- **`第一個詞必須是動詞`**  
	- *取得資訊:* prefix **`get-`**  
	- *過濾資訊:* prefix **`filter-`**  
	- *判斷:* prefix **`is-`**  

##### OOP #####
- *property / method:*  
須遵守 **`變數`** 與 **`函式`** 規則  
- *class:*  
**`大駝峰`**  
- *abstract class:*  
**`大駝峰`**，prefix **`Abstract-`**  
- *trait:*  
**`大駝峰`**，suffix **`-Trait`**  
- *interface:*  
**`大駝峰`**，suffix **`-Interface`**  

<!-- 語法規則 ========================================================================================================-->
***
## 語法規則 ##

##### 資料型態 #####
- 使用 **`int`** 取代 **`integer`**  
- 使用 **`bool`** 取代 **`boolean`**  
- 使用 **`(type)$var`** 取代 **`{type}val($var)`**  

##### array #####
- 只能用 **`"["、"]"`**，禁止使用 **`"array()"`**  
- 新增元素使用 **`$arr[] = $val`** 取代 **`array_push($arr, $val)`**  

##### function / method #####
- 除非無法指定，必須加上 **`parameter type`** 和 **`return type`**  
- 在 function / method 裡面如果需要用到其他物件，除了 Throwable，**`禁止直接 new 物件`**，**`一律使用 singleton 方式取得`**，以方便編寫 test

<!-- 建議事項 ========================================================================================================-->
***
## 建議事項 (非必要) ##
- 一個 function / method 內容不要超過 100 行  

- 不要使用 for / foreach  

- 除非變數是 bool，儘量避免直接使用變數當判斷條件  

- 縮寫名詞視為單字，不需特別強調，ex: isDbError()、getDnsArr()  

- namespace 的 use 順序，global 儘量排在前面，其他則按照字母順序排列  

- 儘量使用 RuntimeException 取代 Exception  

- 儘可能讓 brace (大括號) 階層越少越好，不然階層太多會影響可讀性，而且縮排太多會讓檔案變大  

		if ($bool1) {
			if ($bool2) {
				echo 'result1';
			} else {
				echo 'result2';
			}
		} else {
			echo 'result3';
		}

	=>

		if (!$bool1) {
			echo 'result3';
			exit; // or break, continue ...
		}
		if (!$bool2) {
			echo 'result2';
			exit; // or break, continue ...
		}
		echo 'result1';
