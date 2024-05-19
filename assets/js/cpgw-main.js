class CpgwWalletExtentionHandler {
    constructor(localvalue) {
        this.wrapperclass = '.cpgw_loader_wrap'
        this.confirmMsg = '.cpgw_loader_wrap .cpgw_loader>div'
        this.paymentPrice = localvalue.in_crypto
        this.localizeval = localvalue
        this.WalletName = ''
        this.WalletObject = ''
        this.WalletLink = ''
        this.networkName = ''
        this.networkId = ''
    }

    /**
    * Check Order Status
    */
    checkOrderStatus() {
        if (this.localizeval.order_status == "on-hold" && this.localizeval.transaction_id != "") {
            this.showPassedClass(this.wrapperclass)
            this.hidePassedClass(this.confirmMsg)
            this.addUserMessage(this.localizeval.payment_msg)
            this.addClassForCss('cpgw_payment_sucess')
        }
        else if (this.localizeval.is_paid == 1) {
            this.showPassedClass(this.wrapperclass)
            this.hidePassedClass(this.confirmMsg)
            this.addUserMessage(this.localizeval.payment_msg)
            this.addClassForCss('cpgw_payment_sucess')
        }
        else if (this.localizeval.order_status == "cancelled") {
            let shop_link = "<br><a href=" + this.localizeval.shop_page + ">Go To Shop</a>"
            this.showPassedClass(this.wrapperclass)
            this.hidePassedClass(this.confirmMsg)
            this.addUserMessage(this.localizeval.rejected_msg + shop_link)
            this.addClassForCss('cpgw_payment_rejected')
        }
        else {
            this.showPassedClass('.cmpw_meta_connect')
        }
    }

    /**
    * 
    * @param {*} selectedWallet 
    * @returns selected wallet object
    */
    async getSelectedWallet() {      
        var wallet_object = window.ethereum;
        this.WalletObject = wallet_object;
        return this.WalletObject;
    }

    /**
     * 
     * @param {Check extention enabled or not}
     * @returns 
     */
    isWalletExtentionEnabled() {  
        if (typeof this.WalletObject === 'undefined' || this.WalletObject === '') {       
            const el = document.createElement('div')
            el.innerHTML = "<a href='https://chrome.google.com/webstore/detail/metamask/nkbihfbeogaeaoehlefnkodbefgpgknn?hl=en' target='_blank'>Click Here </a> to install MetaMask extention"
            this.displayPopUp(this.localizeval.const_msg.ext_not_detected, "warning", false, false, el)
            return false;
        }
        return true;
    }

    /**
     * 
     * @param {*} provider 
     * @param {*} wallet_object 
     * Access user account
     */
    async connectUserAccount(provider, wallet_object) {
        try {
            this.displayPopUp(
                this.localizeval.const_msg.connection_establish,
                false,
                false,
                false,
                false,
                false,
                false,
                true,
                true
            );
    
            const account_list = await provider.send("eth_requestAccounts", []);
            const accounts = account_list;
    
            if (accounts[0] !== undefined) {
                Swal.close();             
                const networkResult = await this.getActiveNetwork(provider, wallet_object);
    
                if (networkResult.id !== this.localizeval.network) {
                    this.displayPopUp(
                        this.localizeval.const_msg.required_network,
                        "warning",
                        false,
                        false,
                        false,
                        "Please Switch Network To " + this.localizeval.network_name,
                        false,
                        true,
                        true
                    );
                    this.changeNetwork(this.localizeval.network, wallet_this);
                } else {
                    this.processOrder(provider, wallet_object, accounts);
                }
            }
        } catch (err) {
            console.log(err);
            this.displayPopUp(err.message, 'error', 2000);
        }
    }
    

    /**
     * 
     * @param {*} provider 
     * @param {*} wallet_object 
     * Call extention
     */
    async processOrder(provider, wallet_object, accounts) {
        const object = this;
    
        const activeNetwork = this.getActiveNetwork(provider, wallet_object);
        const networkResult = await activeNetwork;
    
        jQuery('.cmpw_meta_wrapper .active-chain p.cpgw_active_chain').html(networkResult.name);
        jQuery('.cmpw_meta_wrapper .connected-account .account-address').append(accounts);
        this.hidePassedClass('.cmpw_meta_connect');
        this.showPassedClass('.cmpw_meta_wrapper');
    
        if (networkResult.id !== object.localizeval.network) {
            this.displayPopUp(
                object.localizeval.const_msg.required_network,
                "warning",
                false,
                false,
                false,
                "Please Switch Network To " + object.localizeval.network_name,
                false,
                true,
                true
            );
            this.changeNetwork(object.localizeval.network, wallet_object);
        }
    
        jQuery('.pay-btn-wrapper button').on("click", async function () {
            const networkResult = await activeNetwork;
    
            if (networkResult.id !== object.localizeval.network) {
                const result = await object.displayPopUp(
                    object.localizeval.const_msg.required_network,
                    "warning",
                    false,
                    false,
                    false,
                    object.localizeval.const_msg.switch_network + object.localizeval.network_name,
                    true
                );
    
                if (result.isConfirmed) {
                    object.changeNetwork(object.localizeval.network, wallet_object);
                }
            } else {
                object.callMainNetwork(provider, accounts[0], wallet_object);
            }
        });
    }
    

    /**
     * 
     * @param {*} provider 
     * @param {*} accounts 
     * Initiate Payment Process
     */
    async callMainNetwork(provider, accounts, wallet_object) {
        const payButton = jQuery('.pay-btn-wrapper button');
        payButton.removeAttr('disabled');
    
        const confirm_payment = document.createElement('div');
        confirm_payment.innerHTML = this.localizeval.in_crypto + this.localizeval.currency_symbol;
    
        try {
            const confirmResult = await this.displayPopUp(
                this.localizeval.const_msg.confirm_order,
                "warning",
                false,
                false,
                confirm_payment,
                false,
                true,
                false,
                false,
                'Confirm'
            );
    
            if (confirmResult.isConfirmed) {
                await this.callMainNetworkCurrency(accounts, provider, wallet_object);
            }
        } catch (error) {
            console.log(error);
        }
    }
    


    /**
     * 
     * @param {*} account 
     * @param {*} provider 
     * Process Main network currency
     */
    async callMainNetworkCurrency(account, provider) {
        try {
            this.displayPopUp(this.localizeval.confirm_msg, false, this.localizeval.url + "/assets/images/metamask.png", false, false, false, false, true, true);
    
            const contractAddress = this.localizeval.token_address;
            const defaultCurrencies = ["ETH", "BNB"];
    
            if (!defaultCurrencies.includes(this.localizeval.currency_symbol)) {
                await this.processTokenPayments(contractAddress, this.localizeval.receiver, provider);
            } else {
                const signer = provider.getSigner();
                const tx = {
                    from: account,
                    to: this.localizeval.receiver,
                    value: ethers.utils.parseEther(this.paymentPrice)._hex,
                    gasLimit: ethers.utils.hexlify("0x5208"), // 21000
                };
    
                const response = await signer.sendTransaction(tx);
    
                Swal.close();
                const processMessage = document.createElement('div');
                processMessage.innerHTML = `<p class="cpgw_transaction_note">${this.localizeval.const_msg.notice_msg}</p>`;
    
                Swal.fire({
                    title: this.localizeval.process_msg,
                    imageUrl: this.localizeval.url + "/assets/images/metamask.png",
                    footer: processMessage,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    allowOutsideClick: false,
                });
    
                const initialDetails = await provider.getTransaction(response.hash);
                const network = await provider.getNetwork();
                const dynamicTransactionDetails = await this.getDynamicTransactionData(initialDetails, network.chainId);
                const serverResponse = await this.saveTransactionDetails(dynamicTransactionDetails);
                const receipt = await response.wait();
                const details = await provider.getTransaction(receipt.transactionHash);
                const confirmData = await this.getDynamicTransactionData(details, network.chainId);
    
                this.hidePassedClass('.cmpw_meta_wrapper');
                this.showPassedClass('.cpgw_loader_wrap');
                this.addUserMessage(this.localizeval.payment_msg);
                this.addClassForCss('cpgw_payment_sucess');
                this.verifyPayment(confirmData, serverResponse);
            }
        } catch (error) {
            console.log(error);
    
            if (error.code == "4001" || error.error == "Rejected by user") {
                
                this.handleRejectedPayment();
            } else {
                this.displayPopUp(error.message, false, this.localizeval.url + "/assets/images/metamask.png", 5000);
            }
        }
    }
    
    handleRejectedPayment() {
        const shopLink = `<br><a href="${this.localizeval.shop_page}">Go To Shop</a>`;
        this.displayPopUp(this.localizeval.rejected_msg, false, this.localizeval.url + "/assets/images/metamask.png", 2000);
        this.hidePassedClass('.cmpw_meta_wrapper');
        this.addUserMessage(this.localizeval.rejected_msg + shopLink);
        this.addClassForCss('cpgw_payment_rejected');
        this.cancelOrder();
    }
    

    /**
     * 
     * @param {*} transaction 
     * @param {*} rejected 
     * @param {*} secret_code 
     * @param {*} from 
     * Ajax call handling
     */
    verifyPayment(details, signature) {
        let object = this;
        const activechain_id = '0x' + Number(details.chainId).toString(16);
    
        const request_data = {
            'action': 'cpgw_payment_verify',
            'nonce': this.localizeval.nonce,
            'order_id': this.localizeval.id,
            'payment_status': this.localizeval.payment_status,
            'payment_processed': details.hash,
            'selected_network': activechain_id !== '0x0' ? activechain_id : this.localizeval.network,
            'sender': details.from,
            'token_address': details.token_address,
            'receiver': details.receiver,
            'amount': details.amount,
            'signature': signature.data.signature,
        };
    
        // Using Promises for better asynchronous handling
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: this.localizeval.ajax,
                data: request_data,
                success: function (data) {
                    Swal.close();
    
                    if (data.is_paid === true) {
                        object.hidePassedClass('.cmpw_meta_wrapper');
                        object.showPassedClass('.cpgw_loader_wrap');
                        object.addUserMessage(object.localizeval.payment_msg);
                        object.addClassForCss('cpgw_payment_sucess');
                        object.displayPopUp(object.localizeval.payment_msg, false, object.localizeval.url + "/assets/images/metamask.png").then((result) => {
                            if (result.isConfirmed) {
                                if (object.localizeval.redirect !== "") {
                                    window.location.href = object.localizeval.redirect;
                                } else {
                                    location.reload();
                                }
                            }
                        });
                    }
    
                    resolve(data); // Resolve the Promise with success data
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    object.displayPopUp('Error code: ' + textStatus, 'error', false, false, false, errorThrown);
                    console.log('Status: ' + textStatus + ' Error: ' + errorThrown);
                    reject(errorThrown); // Reject the Promise with error details
                },
            });
        });
    }
    


    /**
     * 
     * @param {*} txhash 
     * @param {*} account 
     * Save token in database
     */
    saveTransactionDetails(details) {
        let object = this;
        const request_data = {
            'action': 'cpgw_save_transaction',
            'nonce': this.localizeval.nonce,
            'order_id': this.localizeval.id,
            'transaction_id': details.hash,
            'payment_status': this.localizeval.payment_status,
            'sender': details.from,
            'receiver': details.receiver,
            'amount': details.amount,
            'token_address': details.token_address,
            'signature': this.localizeval.signature,
        };
    
        // Using Promises for better asynchronous handling
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: this.localizeval.ajax,
                data: request_data,
                success: function (data) {
                    if (data.success === false) {
                        // Display error popup and reload the page after a delay
                        object.displayPopUp(data.data, 'error', 2000);
                        setTimeout(function () {
                            location.reload();
                        }, 3500);
                    }
                    if (data.success === true) {
                        resolve(data); // Resolve the Promise with success data
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    console.log('Status: ' + textStatus + ' Error: ' + errorThrown);
                    reject(errorThrown); // Reject the Promise with error details
                },
            });
        });
    }
    

    /**  
     * Cancel Order
     */
    cancelOrder() {
        let object = this;
        var request_data = {
            'action': 'cpgw_cancel_order',
            'nonce': this.localizeval.nonce,
            'order_id': this.localizeval.id               
        };
        return jQuery.ajax({
            type: "post",
            dataType: "json",
            url: this.localizeval.ajax,
            data: request_data,
            success: function (data) {
                if (data.success === false) {     
                    let shop_link = "<br><a href=" + object.localizeval.shop_page + ">Go To Shop</a>"
                    object.showPassedClass('.cpgw_loader_wrap')
                    object.hidePassedClass('.cpgw_loader_wrap.cpgw_loader > div')
                    object.addUserMessage(object.localizeval.rejected_msg + shop_link)
                    object.addClassForCss('cpgw_payment_rejected')               
                    object.displayPopUp(data.data, 'error', 2000)
                    setTimeout(function(){  location.reload(); }, 3500);
                    
                }                   
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Status: " + textStatus + "Error: " + errorThrown);
            }
        })

    }
    

    /**
     * 
     * @param {*} contract_address 
     * @param {*} to_address 
     * @param {*} provider 
     * Process Token Payment
     */
    async processTokenPayments(contract_address, to_address, provider) {
        try {
            if (!contract_address) return;
    
            const abi = [
                "function name() view returns (string)",
                "function symbol() view returns (string)",
                "function balanceOf(address _owner) public view returns (uint256 balance)",
                "function transfer(address _to, uint256 _value) public returns (bool success)",
                "function decimals() view returns (uint256)",
            ];
    
            const signer = provider.getSigner();
            const userAddress = await signer.getAddress();
            const contract = new ethers.Contract(contract_address, abi, signer);
            const decimals = await contract.decimals();
            const amount = ethers.utils.parseUnits(this.paymentPrice, decimals);
            const balance = await contract.balanceOf(userAddress);
            const balanceText = ethers.utils.formatUnits(balance, decimals);
    
            if (Number(balanceText) < this.paymentPrice) {
                this.displayPopUp(`${this.localizeval.const_msg.insufficient_balance} ${balanceText}`, false, this.localizeval.url + "/assets/images/metamask.png");
                this.hidePassedClass('.cpgw_loader_wrap .cpgw_loader>div');
                this.addUserMessage(`${this.localizeval.const_msg.insufficient_balance} ${balanceText}`);
                return;
            }
    
            const tx = await contract.transfer(to_address, amount);
            const network = await provider.getNetwork();
    
            Swal.close();
            const processMessage = document.createElement('div');
            processMessage.innerHTML = `<p class="cpgw_transaction_note">${extradata.const_msg.notice_msg}</p>`;
    
            Swal.fire({
                title: extradata.process_msg,
                imageUrl: this.localizeval.url + "/assets/images/metamask.png",
                footer: processMessage,
                didOpen: () => {
                    Swal.showLoading();
                },
                allowOutsideClick: false,
            });
    
            const initialDetails = await provider.getTransaction(tx.hash);
            const contractSentData = await this.getDynamicTransactionData(initialDetails, network.chainId);
            const serverResponse = await this.saveTransactionDetails(contractSentData);
            const receipt = await tx.wait();
            const details = await provider.getTransaction(receipt.transactionHash);
            const contractConfirmData = await this.getDynamicTransactionData(details, network.chainId);
            this.verifyPayment(contractConfirmData, serverResponse);
        } catch (error) {
            console.log(error);
    
            if (error.code == "4001" || error.error == "Rejected by user") {
                this.handleRejectedPayment();                
            }
    
            const errorMessage = error.code == "-32000" || error.code == "-32603" ?
                extradata.const_msg.insufficient_balance :
                error.message;
    
            this.displayPopUp(errorMessage, 'error', false, false, false, error.message);
        }
    }
    

    
    async getDynamicTransactionData(details, chainIds) {
        try {
            if (details.data !== '0x') {
                const iface = new ethers.utils.Interface(['function transfer(address to, uint256 value)']);
                const parsedData = iface.parseTransaction({ data: details.data });
    
                if (parsedData.name === 'transfer' && parsedData.args.length === 2) {
                    const sentAmount = parsedData.args[1];
                    return {
                        amount: ethers.utils.formatEther(sentAmount),
                        receiver: parsedData.args[0],
                        hash: details.hash,
                        from: details.from,
                        chainId: chainIds,
                        token_address: details.to
                    };
                }
            }
    
            return {
                amount: ethers.utils.formatEther(details.value),
                receiver: details.to,
                hash: details.hash,
                from: details.from,
                chainId: chainIds,
                token_address: details.data !== '0x' ? details.to : this.localizeval.currency_symbol
            };
        } catch (error) {
            console.log(error);
            return null;
        }
    }
    

    /**
     * 
     * @param {pass required chainid} chain_id 
     * @param {wallet object} wallet_object 
     */
    async changeNetwork(chain_id, wallet_object) {
        try {
            const chain_change = await wallet_object.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: chain_id }],
            });
    
            jQuery('.pay-btn-wrapper button').attr('disabled', 'disabled');
            location.reload();
        } catch (switchError) {
            console.log(switchError);
    
            if (switchError.code === 4902) {
                try {
                    await wallet_object.request({
                        method: 'wallet_addEthereumChain',
                        params: [JSON.parse(this.localizeval.network_data)],
                    });
                } catch (addError) {
                    console.log(addError);
                    this.displayPopUp(`Error code: ${addError.code}`, "error", false, false, false, addError.message);
                }
            } else {
                this.displayPopUp(switchError.message, "error");
            }
        }
    }
    

    /**
     * 
     * @param {*} provider 
     * @param {*} wallet_object 
     * @returns currently active network
     */
    async getActiveNetwork(provider, wallet_object) {
        const network = await provider.getNetwork();
        const activechain_id = '0x' + Number(network.chainId).toString(16);
    
        const active_network = this.localizeval.supported_networks[activechain_id] || network.name;
        this.networkName = active_network;
        this.networkId = activechain_id;
    
        return { name: active_network, id: activechain_id };
    }
    



    /**
    * 
    * @param {*} classnames 
    * Display Mentioned Class
    */
    showPassedClass(classnames) {
        jQuery(classnames).show();
    }

    /**
     * 
     * @param {*} classnames
     * Hide Mentioned Class 
     */
    hidePassedClass(classnames) {
        jQuery(classnames).hide();
    }

    /**
     * 
     * @param {*} cssClass
     * add class to html 
     */
    addClassForCss(cssClass) {
        jQuery('.cpgw_loader_wrap .cpgw_loader h2 span').addClass(cssClass)
    }

    /**
     * 
     * @param {*} message 
     * add dyncamic message
     */
    addUserMessage(message) {
        jQuery('.cpgw_loader_wrap .cpgw_loader h2').html("<span>" + message + "</span>");
    }

    /**
     * 
     * @param {receve custom message} msg 
     * @param {icon class} icons 
     * @param {image url} image 
     * @param {timer} time 
     * @param {html} htmls 
     * 
     */
    displayPopUp(msg, icons = false, image = false, time = false, htmls = false, text = false, cancelbtn = false, showloder = false, outsideclick = false, confirmtxt = "Ok", endsession = false) {
        Swal.close()
        let object = Swal.fire({
            title: msg,
            text: text,
            customClass: { container: 'cpgw_main_popup_wrap', popup: 'cpgw_popup' },
            icon: icons,
            html: htmls,
            showCancelButton: cancelbtn,
            confirmButtonColor: '#3085d6',
            confirmButtonText: confirmtxt,
            reverseButtons: true,
            imageUrl: image,
            timer: time,
            didOpen: () => {
                (showloder == true) ? Swal.showLoading() : false
            },
            allowOutsideClick: outsideclick,

        })
        return object;

    }

}
// Initialize the wallet handler
const Wallets = new cpgwWalletExtentionHandler(extradata);

// Check order status initially
Wallets.checkOrderStatus();

// Connect button click handler
jQuery('.cmpw_meta_connect .cpgw_connect_btn button').on("click", async function () {
    try {
        await Wallets.getSelectedWallet();
        if (Wallets.isWalletExtentionEnabled()) {
            const provider = new ethers.providers.Web3Provider(Wallets.WalletObject);
            const accounts = await provider.listAccounts();

            if (accounts.length === 0) {
                Wallets.connectUserAccount(provider, Wallets.WalletObject);
            } else {
                Wallets.processOrder(provider, Wallets.WalletObject, accounts);
            }
        }
    } catch (error) {
        console.error(error);
        // Handle error, if needed
    }
});

// Automatically trigger the connect button click if certain conditions are met
if (extradata.is_paid != 1 && extradata.order_status != "cancelled") {
    jQuery('.cmpw_meta_connect .cpgw_connect_btn button').trigger("click");
}
