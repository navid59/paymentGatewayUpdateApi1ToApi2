# NETOPIA Payments Plugin
## About Plugin
This plugin is mix of API v1 & API v2

## Debuging
    For when plugin is switch 2 API v2, the /v1/updatecredential/ is not defined,...
    So didn't find the Endpoint
    Better to make /v2/updatecredential/


    When Plugin is upgrade ,... The uploaded keys are Not generating
    But if make enable & disable will generate 
    So , keep current method to generate key in auto update, 
    BUT make New Hook for UPGRADE as well to generate the KEYS