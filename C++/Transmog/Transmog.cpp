#include "ScriptMgr.h"
#include "Chat.h"
#include "Player.h"
#include "ObjectMgr.h"
#include "Item.h"
#include "DatabaseEnv.h"
#include "WorldSession.h"
#include "GameEventCallbacks.h"
#include "ObjectExtension.cpp"

/*
Player command to change the look of an item
Player command to restore item looks in a slot
Save all looted items to DB as a bitmask ? 60k items = 10k bytes / player ?
*/

#define NUmberOfTransmogSlots 14

class TransmogStateStore
{
public:
    TransmogStateStore()
    {
        memset(TransmogEntries, 0, sizeof(TransmogEntries));  //no entries in the transmog store
        LoadedValuesFromDB = false;
    }

    //set or reset a visible item entry to another item entry
    void SetTransmog(Player *player, uint32 SlotId, uint32 ItemId)
    {
        //sanity checks
        if (player == NULL || SlotId >= EQUIPMENT_SLOT_END)
            return;

        //any entry, could be 0 also
        TransmogEntries[SlotId] = ItemId;

        //if it was a reset, try to gte the original entry of the item in this slot
        if (ItemId == 0)
        {
            Item *it = player->GetItemByPos(SlotId);
            if (it != NULL)
                ItemId = it->GetEntry();
        }

        //finally set the slot to this item
        player->SetUInt32Value(PLAYER_VISIBLE_ITEM_1_ENTRYID + (SlotId * (PLAYER_VISIBLE_ITEM_2_ENTRYID - PLAYER_VISIBLE_ITEM_1_ENTRYID)), ItemId);
    }

    //only save and load these slots. Ignore the rest
    const int TransmogItemsSlots[NUmberOfTransmogSlots] = { EQUIPMENT_SLOT_HEAD, EQUIPMENT_SLOT_SHOULDERS, EQUIPMENT_SLOT_CHEST, EQUIPMENT_SLOT_BODY, EQUIPMENT_SLOT_WAIST, EQUIPMENT_SLOT_LEGS, EQUIPMENT_SLOT_FEET, EQUIPMENT_SLOT_HANDS, EQUIPMENT_SLOT_BACK, EQUIPMENT_SLOT_MAINHAND, EQUIPMENT_SLOT_OFFHAND, EQUIPMENT_SLOT_RANGED, EQUIPMENT_SLOT_TABARD, EQUIPMENT_SLOT_WRISTS };

    //load transmog state from DB
    void LoadTransmog(Player *player)
    {
        //load xmog setup
        char Query[5000];
        sprintf_s(Query, sizeof(Query), "SELECT * from character_transmog where GUID = %d", (uint32)player->GetGUID().GetRawValue());
        QueryResult result = CharacterDatabase.Query(Query);
        if (!result || result->GetRowCount() != 1)
            return;

        Field* fields = result->Fetch();
        for (uint32 i = 0; i < NUmberOfTransmogSlots; i++)
            if(fields[i + 1].GetUInt32()!=0)
                SetTransmog(player, TransmogItemsSlots[i], fields[i + 1].GetUInt32());

        LoadedValuesFromDB = true;
    }

    //Do we have any items sets to transmog ?
    bool HasTransmogs()
    {
        for (int i = 0; i<EQUIPMENT_SLOT_END; i++)
            if (TransmogEntries[i] != 0)
            {
                return true;
            }
        return false;
    }

    //save transmog state to DB
    void SaveTransmog(Player *player)
    {
        //no changes made, nothing to save
        if (HasTransmogs() == false)
        {
            if (LoadedValuesFromDB == true)
            {
                char Query[5000];
                sprintf_s(Query, sizeof(Query), "delete from character_transmog where guid = %d", (uint32)player->GetGUID().GetRawValue());
                CharacterDatabase.Execute(Query);
            }
            return;
        }

        //save transmog items ( if there are any )
        char Query[5000];
        sprintf_s(Query, sizeof(Query), "replace INTO character_transmog VALUES(%d,", (uint32)player->GetGUID().GetRawValue());
        for (uint32 i = 0; i<NUmberOfTransmogSlots - 1; i++)
            sprintf_s(Query, sizeof(Query), "%s%d,", Query, TransmogEntries[TransmogItemsSlots[i]]);
        sprintf_s(Query, sizeof(Query), "%s%d)", Query, TransmogEntries[TransmogItemsSlots[NUmberOfTransmogSlots - 1]]);
        CharacterDatabase.Execute(Query);
    }
private:
    uint32  TransmogEntries[EQUIPMENT_SLOT_END];
    bool    LoadedValuesFromDB;
};

void SetTransmog(Player *player, uint32 SlotId, uint32 Entry)
{
    TransmogStateStore *ts = player->GetCreateExtension<TransmogStateStore>(OE_PLAYER_USED_XMOG_STORE);
    ts->SetTransmog(player, SlotId, Entry);
}

void ResetTransmog(Player *player, uint32 SlotId)
{
    TransmogStateStore *ts = player->GetExtension<TransmogStateStore>(OE_PLAYER_USED_XMOG_STORE);
    if (ts == NULL)
    {
        Item *it = player->GetItemByPos(SlotId);
        if (it != NULL)
            player->SetUInt32Value(PLAYER_VISIBLE_ITEM_1_ENTRYID + (SlotId * (PLAYER_VISIBLE_ITEM_2_ENTRYID - PLAYER_VISIBLE_ITEM_1_ENTRYID)), it->GetEntry());
        else
            player->SetUInt32Value(PLAYER_VISIBLE_ITEM_1_ENTRYID + (SlotId * (PLAYER_VISIBLE_ITEM_2_ENTRYID - PLAYER_VISIBLE_ITEM_1_ENTRYID)), 0);
    }
    else
    {
        ts->SetTransmog(player, SlotId, 0);
    }
}

const char *FindNextParam(const char *str)
{
    if (str == NULL)
        return NULL;
    while (*str != 0 && *str != ' ')
        str++;
    if (*str == ' ')
        str++;
    return str;
}

int FindNextIntParam(const char *str)
{
    const char *ParamStart = FindNextParam(str);
    if (ParamStart != NULL)
        return atoi(ParamStart);
    return -1;
}

int StringSlotToSlot(const char *strSlot)
{
    if (strSlot == NULL)
        return -1;
    if (strstr(strSlot, "head") == strSlot)
        return EQUIPMENT_SLOT_HEAD;
    else if (strstr(strSlot, "shoulder") == strSlot)
        return EQUIPMENT_SLOT_SHOULDERS;
    else if (strstr(strSlot, "chest") == strSlot)
        return EQUIPMENT_SLOT_CHEST;
    else if (strstr(strSlot, "body") == strSlot)
        return EQUIPMENT_SLOT_BODY;
    else if (strstr(strSlot, "waist") == strSlot)
        return EQUIPMENT_SLOT_WAIST;
    else if (strstr(strSlot, "legs") == strSlot)
        return EQUIPMENT_SLOT_LEGS;
    else if (strstr(strSlot, "feet") == strSlot)
        return EQUIPMENT_SLOT_FEET;
    else if (strstr(strSlot, "hands") == strSlot)
        return EQUIPMENT_SLOT_HANDS;
    else if (strstr(strSlot, "back") == strSlot)
        return EQUIPMENT_SLOT_BACK;
    else if (strstr(strSlot, "main") == strSlot)
        return EQUIPMENT_SLOT_MAINHAND;
    else if (strstr(strSlot, "off") == strSlot)
        return EQUIPMENT_SLOT_OFFHAND;
    else if (strstr(strSlot, "ranged") == strSlot)
        return EQUIPMENT_SLOT_RANGED;
    else if (strstr(strSlot, "tabard") == strSlot)
        return EQUIPMENT_SLOT_TABARD;
    else if (strstr(strSlot, "wrists") == strSlot)
        return EQUIPMENT_SLOT_WRISTS;
    return -1;
}

uint8 FindEquipSlot(ItemTemplate const* proto)
{
    switch (proto->InventoryType)
    {
    case INVTYPE_HEAD:
        return EQUIPMENT_SLOT_HEAD;
    case INVTYPE_NECK:
        return EQUIPMENT_SLOT_NECK;
    case INVTYPE_SHOULDERS:
        return EQUIPMENT_SLOT_SHOULDERS;
    case INVTYPE_BODY:
        return EQUIPMENT_SLOT_BODY;
    case INVTYPE_CHEST:
        return EQUIPMENT_SLOT_CHEST;
    case INVTYPE_ROBE:
        return EQUIPMENT_SLOT_CHEST;
    case INVTYPE_WAIST:
        return EQUIPMENT_SLOT_WAIST;
    case INVTYPE_LEGS:
        return EQUIPMENT_SLOT_LEGS;
    case INVTYPE_FEET:
        return EQUIPMENT_SLOT_FEET;
    case INVTYPE_WRISTS:
        return EQUIPMENT_SLOT_WRISTS;
    case INVTYPE_HANDS:
        return EQUIPMENT_SLOT_HANDS;
    case INVTYPE_CLOAK:
        return EQUIPMENT_SLOT_BACK;
    case INVTYPE_WEAPON:
        return EQUIPMENT_SLOT_MAINHAND;
    case INVTYPE_SHIELD:
        return EQUIPMENT_SLOT_OFFHAND;
    case INVTYPE_RANGED:
        return EQUIPMENT_SLOT_RANGED;
    case INVTYPE_2HWEAPON:
        return EQUIPMENT_SLOT_MAINHAND;
    case INVTYPE_TABARD:
        return EQUIPMENT_SLOT_TABARD;
    case INVTYPE_WEAPONMAINHAND:
        return EQUIPMENT_SLOT_MAINHAND;
    case INVTYPE_WEAPONOFFHAND:
        return EQUIPMENT_SLOT_OFFHAND;
    case INVTYPE_HOLDABLE:
        return EQUIPMENT_SLOT_OFFHAND;
    case INVTYPE_THROWN:
        return EQUIPMENT_SLOT_RANGED;
    case INVTYPE_RANGEDRIGHT:
        return EQUIPMENT_SLOT_RANGED;
    default:
        return NULL_SLOT;
    }
}

bool CheckValidClientCommand(const char *cmsg, int32 type, const char * channel)
{
//    printf("received message '%s' of type %d\n", cmsg, type);
    //self whisper
    if (type != CHAT_MSG_WHISPER && type != CHAT_MSG_GUILD)
    {
        //           printf("Only accept whispers\n");
        return false;
    }
    //need command format
    if (cmsg[0] != '#')
    {
        if (cmsg[0] == '\t' && cmsg[1] == '#')
            return true;
        return false;
    }
    return true;
}

class TC_GAME_API TransmogChatListnerScript : public PlayerScript
{
public:
    TransmogChatListnerScript() : PlayerScript("TransmogChatListnerScript") {}
    void OnLogin(Player* player, bool firstLogin)
    {
        TransmogStateStore *ts = new TransmogStateStore;
        ts->LoadTransmog(player);
        if (ts->HasTransmogs() && player->GetExtension<TransmogStateStore>(OE_PLAYER_USED_XMOG_STORE) == NULL)
        {
            player->SetExtension<TransmogStateStore>(OE_PLAYER_USED_XMOG_STORE, ts);
        }
        else
        {
            delete ts;
        }
    }

    // Called when a player logs out.
    void OnLogout(Player* player)
    {
        TransmogStateStore *ts = player->GetExtension<TransmogStateStore>(OE_PLAYER_USED_XMOG_STORE);
        if (ts == NULL)
            return;
        ts->SaveTransmog(player);
    }

    void OnDelete(ObjectGuid guid, uint32 accountId)
    {
        char Query[5000];
        sprintf_s(Query, sizeof(Query), "delete from character_transmog where GUID=%d", (uint32)guid.GetRawValue());
        CharacterDatabase.Execute(Query);
    }
};

bool IsTransmogItem(const ItemTemplate *it)
{
    if (it->InventoryType == INVTYPE_NON_EQUIP || it->InventoryType == INVTYPE_FINGER || it->InventoryType == INVTYPE_TRINKET || it->InventoryType == INVTYPE_BAG
        || it->InventoryType == INVTYPE_AMMO || it->InventoryType == INVTYPE_QUIVER || it->InventoryType == INVTYPE_RELIC
        )
        return false; // about 13000 items in this category. Remaining 23174 to add to DB / account
    return true;
}

bool HasTransmog(unsigned int AcctId, unsigned int ItemId)
{
    char Query[5000];
    sprintf_s(Query, sizeof(Query), "SELECT ItemId FROM account_transmog_items where AcctId=%d and ItemId=%d", AcctId, ItemId);
    QueryResult result = CharacterDatabase.Query(Query);

    if (result)
    {
        Field* fields = result->Fetch();
        uint32 tempId = fields[0].GetUInt32();
        if (tempId == ItemId)
            return true;
    }

    return false;
}

void AddTransmog(unsigned int AcctId, unsigned int ItemId)
{
    // add it to the account to be able to use it on any char as transmog
    char Query[5000];
    sprintf_s(Query, sizeof(Query), "INSERT IGNORE INTO account_transmog_items(AcctId, ItemId) VALUES(%d,%d)", AcctId, ItemId);
    CharacterDatabase.Execute(Query);
}

void TransmogItemOnLoot(void *p, void *)
{
    CP_ITEM_STORED *params = PointerCast(CP_ITEM_STORED, p);
    if (params->Owner == NULL || params->ItemTemplate == NULL || params->Owner->GetSession() == NULL)
        return;

    //do not handle items that can not be used as transmog
    if (IsTransmogItem(params->ItemTemplate) == false)
        return;

//    AddTransmog((uint32)params->Owner->GetSession()->GetAccountId(), (uint32)params->ItemTemplate->ItemId, (uint32)params->ItemTemplate->InventoryType);
    AddTransmog((uint32)params->Owner->GetSession()->GetAccountId(), (uint32)params->ItemTemplate->ItemId);
}

void TParseClientUserCommand(Player* player, uint32 type, const char *cmsg)
{
    //        printf("got command %s\n",cmsg);
    if (CheckValidClientCommand(cmsg, type, NULL) == false)
        return;
    //do we want to set the difficulty ?
    if (strstr(cmsg, "#csTransmogSet ") == cmsg)
    {
        // get the destination slot where we want to transmog
        const char *SlotName = FindNextParam(cmsg);
        int SlotId = StringSlotToSlot(SlotName);
        if (SlotId == -1)
        {
            player->BroadcastMessage("No valid destination slot was given. Can not set transmog to %s\n", SlotName);
            return;
        }

        //get the item we want to transmog to
        const char *ItemNameOrId = FindNextParam(SlotName);
        int ItemId = atoi(ItemNameOrId);
        if (ItemId == 0)
        {
            ResetTransmog(player, SlotId);
            player->BroadcastMessage("No valid source item id %d was given. Can not set transmog\n", ItemNameOrId);
            return;
        }

        //check if items is valid
        ItemTemplate const* proto = sObjectMgr->GetItemTemplate(ItemId);
        if (proto == NULL)
        {
            ResetTransmog(player, SlotId);
            player->BroadcastMessage("No valid source item id %d was given. Can not set transmog\n", ItemNameOrId);
            return;
        }

        //check if the item can be put in that slot
        uint8 PossibleSlot = FindEquipSlot(proto);
        bool SlotMatches = false;
        if (PossibleSlot == SlotId)
            SlotMatches = true;
        else if (SlotId == EQUIPMENT_SLOT_OFFHAND && proto->InventoryType == INVTYPE_WEAPON)
            SlotMatches = true;
        else if (SlotId == EQUIPMENT_SLOT_OFFHAND && proto->InventoryType == INVTYPE_2HWEAPON && (player->CanDualWield() || player->CanTitanGrip()))
            SlotMatches = true;
        if (SlotMatches == false)
        {
            player->BroadcastMessage("Can not equip this item into slot %s\n", SlotName);
            return;
        }

        //check if we already have this item. Just to avoid spamming server with same command
        if (player->GetUInt32Value(PLAYER_VISIBLE_ITEM_1_ENTRYID + (SlotId * (PLAYER_VISIBLE_ITEM_2_ENTRYID - PLAYER_VISIBLE_ITEM_1_ENTRYID))) == ItemId)
        {
            //                printf("Already have this item set into this slot\n");
            return;
        }

        //check if account ever seen this item
        bool HaveTransm = HasTransmog(player->GetSession()->GetAccountId(), ItemId);
        if(HaveTransm == false)
        {
            player->BroadcastMessage("You need to loot this item to use it as transmog\n");
            return;
        }

        SetTransmog(player, SlotId, ItemId);
    }
}

void RBAC_Transmog_Xmog(Player* player, const char *SlotName, const char *ItemNameOrId) {

        // get the destination slot where we want to transmog
        int SlotId = StringSlotToSlot(SlotName);
        if (SlotId == -1)
        {
            player->BroadcastMessage("No valid destination slot was given. Can not set transmog to %s\n", SlotName);
            return;
        }

        //get the item we want to transmog to
        int ItemId = atoi(ItemNameOrId);
        if (ItemId == 0)
        {
            ResetTransmog(player, SlotId);
            player->BroadcastMessage("No valid source item id %d was given. Can not set transmog\n", ItemNameOrId);
            return;
        }

        //check if items is valid
        ItemTemplate const* proto = sObjectMgr->GetItemTemplate(ItemId);
        if (proto == NULL)
        {
            ResetTransmog(player, SlotId);
            player->BroadcastMessage("No valid source item id %d was given. Can not set transmog\n", ItemNameOrId);
            return;
        }

        //check if the item can be put in that slot
        uint8 PossibleSlot = FindEquipSlot(proto);
        bool SlotMatches = false;
        if (PossibleSlot == SlotId)
            SlotMatches = true;
        else if (SlotId == EQUIPMENT_SLOT_OFFHAND && proto->InventoryType == INVTYPE_WEAPON)
            SlotMatches = true;
        else if (SlotId == EQUIPMENT_SLOT_OFFHAND && proto->InventoryType == INVTYPE_2HWEAPON && (player->CanDualWield() || player->CanTitanGrip()))
            SlotMatches = true;
        if (SlotMatches == false)
        {
            player->BroadcastMessage("Can not equip this item into slot %s\n", SlotName);
            return;
        }

        //check if we already have this item. Just to avoid spamming server with same command
        if (player->GetUInt32Value(PLAYER_VISIBLE_ITEM_1_ENTRYID + (SlotId * (PLAYER_VISIBLE_ITEM_2_ENTRYID - PLAYER_VISIBLE_ITEM_1_ENTRYID))) == ItemId)
        {
            //                printf("Already have this item set into this slot\n");
            return;
        }

        //check if account ever seen this item
        char Query[5000];
        sprintf_s(Query, sizeof(Query), "SELECT ItemId FROM account_transmog_items where AcctId=%d and ItemId=%d", (uint32)player->GetSession()->GetAccountId(), (uint32)ItemId);
        QueryResult result = CharacterDatabase.Query(Query);

        if (result)
        {
            Field* fields = result->Fetch();
            uint32 tempId = fields[0].GetUInt32();
            if (tempId != ItemId)
            {
                player->BroadcastMessage("You need to loot this item to use it as transmog\n");
                return;
            }
        }
        else
        {
            player->BroadcastMessage("You need to loot this item to use it as transmog\n");
            return;
        }

        SetTransmog(player, SlotId, ItemId);
    
}

void TOnChatMessageReceived(void *p, void *)
{
    CP_CHAT_RECEIVED *params = PointerCast(CP_CHAT_RECEIVED, p);

    //check for strings that might be our commands
    TParseClientUserCommand(params->SenderPlayer, params->MsgType, params->Msg->c_str());
}

void AddTransmogScripts()
{
    //CREATE TABLE `account_transmog_items` ( `AcctId` INT NULL, `ItemId` INT NULL );
    //ALTER TABLE `account_transmog_items` ADD INDEX `AcctId` (`AcctId`, `ItemId`) USING BTREE;
    //CREATE UNIQUE INDEX relation ON account_transmog_items (AcctId, ItemId);

    //CREATE TABLE `character_transmog` ( `GUID` INT NULL, `SlotHead` INT NULL, `SlotShoulder` INT NULL, `SlotChest` INT NULL, `SlotBody` INT NULL, `SlotWaist` INT NULL, `SlotLegs` INT NULL, `SlotFeet` INT NULL, `SlotHands` INT NULL, `SlotBack` INT NULL, `SlotMHand` INT NULL, `SlotOHand` INT NULL, `SlotRanged` INT NULL, `SlotTabard` INT NULL, `SlotWrists` INT NULL );
    //ALTER TABLE `character_transmog` ADD INDEX `AcctId` (`GUID`) USING BTREE;
    //CREATE UNIQUE INDEX relation ON character_transmog (GUID);
    //alter table `account_transmog_items` add column `SlotId` int(3) NULL after `ItemId`

    new TransmogChatListnerScript();
    RegisterCallbackFunction(CALLBACK_TYPE_PLAYER_ITEM_STORED, TransmogItemOnLoot, NULL);
    RegisterCallbackFunction(CALLBACK_TYPE_CHAT_RECEIVED, TOnChatMessageReceived, NULL);
}
